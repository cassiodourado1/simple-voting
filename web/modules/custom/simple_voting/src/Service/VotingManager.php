<?php

namespace Drupal\simple_voting\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\simple_voting\Entity\VotingRecord;

/**
 * Central service for all voting business logic.
 *
 * Used by both CMS forms and API controllers to ensure rules are applied
 * consistently regardless of the entry point.
 */
class VotingManager {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Returns whether voting is globally enabled.
   */
  public function isVotingEnabled(): bool {
    return (bool) $this->configFactory
      ->get('simple_voting.settings')
      ->get('voting_enabled');
  }

  /**
   * Loads a VotingQuestion by its internal ID.
   *
   * @return \Drupal\simple_voting\Entity\VotingQuestion|null
   */
  public function loadQuestion(int $questionId): ?object {
    return $this->entityTypeManager
      ->getStorage('voting_question')
      ->load($questionId);
  }

  /**
   * Loads a VotingQuestion by its UUID.
   *
   * @return \Drupal\simple_voting\Entity\VotingQuestion|null
   */
  public function loadQuestionByUuid(string $uuid): ?object {
    $results = $this->entityTypeManager
      ->getStorage('voting_question')
      ->loadByProperties(['uuid' => $uuid]);

    return $results ? reset($results) : NULL;
  }

  /**
   * Loads a VotingOption by its internal ID.
   *
   * @return \Drupal\simple_voting\Entity\VotingOption|null
   */
  public function loadOption(int $optionId): ?object {
    return $this->entityTypeManager
      ->getStorage('voting_option')
      ->load($optionId);
  }

  /**
   * Checks whether a user has already voted on a given question.
   */
  public function hasUserVoted(int $questionId, int $userId): bool {
    $result = $this->database->select('voting_record', 'vr')
      ->fields('vr', ['id'])
      ->condition('question_id', $questionId)
      ->condition('user_id', $userId)
      ->range(0, 1)
      ->execute()
      ->fetchField();

    return (bool) $result;
  }

  /**
   * Casts a vote after performing all business rule validations.
   *
   * @param int $questionId
   *   The question being voted on.
   * @param int $optionId
   *   The chosen option.
   * @param int $userId
   *   The voting user.
   * @param string $source
   *   Origin of the vote: 'cms' or 'api'.
   *
   * @return array
   *   ['success' => bool, 'message' => string]
   */
  public function castVote(int $questionId, int $optionId, int $userId, string $source = 'cms'): array {
    $logger = $this->loggerFactory->get('simple_voting');

    // 1. Global voting flag.
    if (!$this->isVotingEnabled()) {
      $logger->warning('Vote attempt while voting is globally disabled. User: @uid', ['@uid' => $userId]);
      return ['success' => FALSE, 'message' => 'Voting is currently disabled.'];
    }

    // 2. Question must exist and be active.
    $question = $this->loadQuestion($questionId);
    if (!$question || !$question->get('status')->value) {
      $logger->warning('Vote attempt on invalid/inactive question @qid by user @uid.', [
        '@qid' => $questionId,
        '@uid' => $userId,
      ]);
      return ['success' => FALSE, 'message' => 'Question not found or inactive.'];
    }

    // 3. Option must exist and belong to the question.
    $option = $this->loadOption($optionId);
    if (!$option || (int) $option->get('question_id')->target_id !== $questionId) {
      $logger->warning('Vote attempt with invalid option @oid for question @qid by user @uid.', [
        '@oid' => $optionId,
        '@qid' => $questionId,
        '@uid' => $userId,
      ]);
      return ['success' => FALSE, 'message' => 'Invalid option for this question.'];
    }

    // 4. User must not have voted before (application-level check).
    if ($this->hasUserVoted($questionId, $userId)) {
      $logger->notice('Duplicate vote attempt on question @qid by user @uid.', [
        '@qid' => $questionId,
        '@uid' => $userId,
      ]);
      return ['success' => FALSE, 'message' => 'You have already voted on this question.'];
    }

    // 5. Persist the vote. The unique key (question_id, user_id) on the
    //    voting_record table is the last line of defense under concurrency.
    try {
      $record = VotingRecord::create([
        'question_id' => $questionId,
        'option_id'   => $optionId,
        'user_id'     => $userId,
        'source'      => $source,
      ]);
      $record->save();

      $logger->info('Vote cast on question @qid option @oid by user @uid via @source.', [
        '@qid'    => $questionId,
        '@oid'    => $optionId,
        '@uid'    => $userId,
        '@source' => $source,
      ]);

      return ['success' => TRUE, 'message' => 'Vote registered successfully.'];
    }
    catch (\Exception $e) {
      // Catches unique key violations from concurrent requests.
      $logger->error('Failed to save vote on question @qid by user @uid: @msg', [
        '@qid' => $questionId,
        '@uid' => $userId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Could not register vote. You may have already voted.'];
    }
  }

  /**
   * Returns vote results for a question.
   *
   * @return array
   *   ['show_results' => bool, 'total' => int, 'options' => [...]]
   */
  public function getResults(int $questionId): array {
    $question = $this->loadQuestion($questionId);
    if (!$question) {
      return ['show_results' => FALSE, 'total' => 0, 'options' => []];
    }

    $showResults = (bool) $question->get('show_results')->value;

    if (!$showResults) {
      return ['show_results' => FALSE, 'total' => 0, 'options' => []];
    }

    // Aggregate votes per option.
    $query = $this->database->select('voting_record', 'vr');
    $query->addField('vr', 'option_id');
    $query->addExpression('COUNT(vr.id)', 'total');
    $query->condition('vr.question_id', $questionId);
    $query->groupBy('vr.option_id');
    $rows = $query->execute()->fetchAllKeyed();

    $total = array_sum($rows);

    $options = $this->entityTypeManager
      ->getStorage('voting_option')
      ->loadByProperties(['question_id' => $questionId]);

    $results = [];
    foreach ($options as $option) {
      $count = (int) ($rows[$option->id()] ?? 0);
      $results[] = [
        'id'          => $option->id(),
        'title'       => $option->label(),
        'votes'       => $count,
        'percentage'  => $total > 0 ? round(($count / $total) * 100, 1) : 0,
      ];
    }

    return [
      'show_results' => TRUE,
      'total'        => $total,
      'options'      => $results,
    ];
  }

}
