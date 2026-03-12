<?php

namespace Drupal\simple_voting\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * List builder for VotingQuestion entities.
 */
class VotingQuestionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['add_button'] = [
      '#type'   => 'link',
      '#title'  => $this->t('+ Adicionar Pergunta'),
      '#url'    => Url::fromRoute('entity.voting_question.add_form'),
      '#attributes' => [
        'class' => ['button', 'button--primary', 'button-action'],
      ],
      '#weight' => -10,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id']           = $this->t('ID');
    $header['title']        = $this->t('Título');
    $header['status']       = $this->t('Ativo');
    $header['show_results'] = $this->t('Exibir Resultados');
    $header['options']      = $this->t('Opções');
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
    $row['status']       = $entity->get('status')->value ? $this->t('Sim') : $this->t('Não');
    $row['show_results'] = $entity->get('show_results')->value ? $this->t('Sim') : $this->t('Não');
    $row['options']      = Link::createFromRoute(
      $this->t('Gerenciar Opções'),
      'simple_voting.question_options',
      ['voting_question' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
