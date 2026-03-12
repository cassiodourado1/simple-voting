<?php

namespace Drupal\simple_voting\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * List builder for VotingQuestion entities.
 */
class VotingQuestionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id']           = $this->t('ID');
    $header['title']        = $this->t('Title');
    $header['status']       = $this->t('Active');
    $header['show_results'] = $this->t('Show Results');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\simple_voting\Entity\VotingQuestion $entity */
    $row['id']           = $entity->id();
    $row['title']        = Link::createFromRoute(
      $entity->label(),
      'entity.voting_question.edit_form',
      ['voting_question' => $entity->id()]
    );
    $row['status']       = $entity->get('status')->value ? $this->t('Yes') : $this->t('No');
    $row['show_results'] = $entity->get('show_results')->value ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
