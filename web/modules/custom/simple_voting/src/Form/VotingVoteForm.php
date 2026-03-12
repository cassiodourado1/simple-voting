<?php

namespace Drupal\simple_voting\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\simple_voting\Entity\VotingQuestion;
use Drupal\simple_voting\Service\VotingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Voting form displayed on the CMS voting page and in the home block.
 */
class VotingVoteForm extends FormBase {

  public function __construct(
    protected VotingManager $votingManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('simple_voting.voting_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'simple_voting_vote_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\simple_voting\Entity\VotingQuestion|null $voting_question
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?VotingQuestion $voting_question = NULL): array {
    if (!$voting_question) {
      $form['message'] = ['#markup' => $this->t('No question found.')];
      return $form;
    }

    $form['#question'] = $voting_question;
    $form['question_id'] = [
      '#type'  => 'hidden',
      '#value' => $voting_question->id(),
    ];

    // Voting globally disabled.
    if (!$this->votingManager->isVotingEnabled()) {
      $form['message'] = ['#markup' => $this->t('Voting is currently unavailable.')];
      return $form;
    }

    // Question inactive.
    if (!$voting_question->get('status')->value) {
      $form['message'] = ['#markup' => $this->t('Esta pergunta não está mais aceitando votos.')];
      return $form;
    }

    $currentUser = \Drupal::currentUser();

    // User already voted — show results if allowed.
    if ($this->votingManager->hasUserVoted((int) $voting_question->id(), (int) $currentUser->id())) {
      return $this->buildResultsSection($form, $voting_question);
    }

    // Build options as radio cards with image + description.
    $options = \Drupal::entityTypeManager()
      ->getStorage('voting_option')
      ->loadByProperties(['question_id' => $voting_question->id()]);

    uasort($options, fn($a, $b) => $a->get('weight')->value <=> $b->get('weight')->value);

    $form['title'] = [
      '#markup' => '<h2>' . $voting_question->label() . '</h2>',
    ];

    // Build options as radio cards with image + description.
    $options = \Drupal::entityTypeManager()
      ->getStorage('voting_option')
      ->loadByProperties(['question_id' => $voting_question->id()]);

    uasort($options, fn($a, $b) => $a->get('weight')->value <=> $b->get('weight')->value);

    $radioOptions = [];
    foreach ($options as $option) {
      $imageHtml = '';
      $imageItems = $option->get('image');
      if (!$imageItems->isEmpty()) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $imageItems->entity;
        if ($file) {
          $uri = $file->getFileUri();
          $url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
          $alt = $imageItems->alt ?: $option->label();
          $imageHtml = '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="' . htmlspecialchars($alt, ENT_QUOTES) . '" class="voting-option-img">';
        }
      }

      $description = $option->get('description')->value;
      $descHtml = $description
        ? '<span class="voting-option-desc">' . htmlspecialchars($description, ENT_QUOTES) . '</span>'
        : '';

      $radioOptions[$option->id()] = Markup::create(
        '<span class="voting-option-body">'
        . $imageHtml
        . '<span class="voting-option-text">'
        . '<span class="voting-option-title">' . htmlspecialchars($option->label(), ENT_QUOTES) . '</span>'
        . $descHtml
        . '</span>'
        . '</span>'
      );
    }

    $form['option_id'] = [
      '#type'     => 'radios',
      '#title'    => $this->t('Escolha uma opção'),
      '#options'  => $radioOptions,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Votar'),
    ];

    $form['back'] = $this->buildBackButton();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $questionId = (int) $form_state->getValue('question_id');
    $optionId   = (int) $form_state->getValue('option_id');
    $userId     = (int) \Drupal::currentUser()->id();

    $result = $this->votingManager->castVote($questionId, $optionId, $userId, 'cms');

    if ($result['success']) {
      $this->messenger()->addStatus($this->t('Seu voto foi registrado com sucesso!'));
    }
    else {
      $this->messenger()->addError($this->t('@msg', ['@msg' => $result['message']]));
    }
  }

  /**
   * Builds the results display section.
   */
  protected function buildResultsSection(array $form, VotingQuestion $question): array {
    $results = $this->votingManager->getResults((int) $question->id());

    $form['title'] = [
      '#markup' => '<h2>' . $question->label() . '</h2>',
    ];

    if (!$results['show_results']) {
      $form['message'] = ['#markup' => $this->t('Obrigado por votar! Os resultados não estão disponíveis publicamente.')];
      $form['back'] = $this->buildBackButton();
      return $form;
    }

    $form['results'] = [
      '#theme'   => 'item_list',
      '#title'   => $this->t('Resultados (@total votos)', ['@total' => $results['total']]),
      '#items'   => array_map(
        fn($o) => $this->t('@title: @votes votos (@pct%)', [
          '@title' => $o['title'],
          '@votes' => $o['votes'],
          '@pct'   => $o['percentage'],
        ]),
        $results['options']
      ),
    ];

    $form['back'] = $this->buildBackButton();

    return $form;
  }

  /**
   * Builds a "Voltar" link to the voting list.
   */
  protected function buildBackButton(): array {
    return [
      '#type'       => 'link',
      '#title'      => $this->t('← Voltar para votações'),
      '#url'        => \Drupal\Core\Url::fromRoute('simple_voting.list'),
      '#attributes' => ['class' => ['voting-back-link']],
    ];
  }

}
