<?php

namespace Drupal\simple_voting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\simple_voting\Entity\VotingQuestion;

/**
 * Controller for managing options of a VotingQuestion.
 */
class VotingOptionAdminController extends ControllerBase {

  /**
   * Lists all options for a given question with edit/delete/add links.
   */
  public function listing(VotingQuestion $voting_question): array {
    $options = $this->entityTypeManager()
      ->getStorage('voting_option')
      ->loadByProperties(['question_id' => $voting_question->id()]);

    // Sort by weight.
    uasort($options, fn($a, $b) => $a->get('weight')->value <=> $b->get('weight')->value);

    $rows = [];
    foreach ($options as $option) {
      $rows[] = [
        $option->id(),
        $option->label(),
        $option->get('description')->value ?: '—',
        [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'edit' => [
                'title' => $this->t('Editar'),
                'url'   => Url::fromRoute('entity.voting_option.edit_form', ['voting_option' => $option->id()]),
              ],
              'delete' => [
                'title' => $this->t('Excluir'),
                'url'   => Url::fromRoute('entity.voting_option.delete_form', ['voting_option' => $option->id()]),
              ],
            ],
          ],
        ],
      ];
    }

    $addUrl = Url::fromRoute('entity.voting_option.add_form', ['voting_question' => $voting_question->id()]);

    return [
      'add_button' => [
        '#type'       => 'link',
        '#title'      => $this->t('+ Adicionar Opção'),
        '#url'        => $addUrl,
        '#attributes' => ['class' => ['button', 'button--primary', 'button-action']],
        '#weight'     => -10,
      ],
      'table' => [
        '#type'   => 'table',
        '#caption' => $this->t('Opções da pergunta: %title', ['%title' => $voting_question->label()]),
        '#header' => [
          $this->t('ID'),
          $this->t('Título'),
          $this->t('Descrição'),
          $this->t('Operações'),
        ],
        '#rows'  => $rows,
        '#empty' => $this->t('Nenhuma opção cadastrada. Clique em "+ Adicionar Opção" para começar.'),
      ],
      'back' => [
        '#type'       => 'link',
        '#title'      => $this->t('← Voltar para perguntas'),
        '#url'        => Url::fromRoute('simple_voting.question_list'),
        '#attributes' => ['class' => ['voting-back-link']],
        '#weight'     => 10,
      ],
    ];
  }

}
