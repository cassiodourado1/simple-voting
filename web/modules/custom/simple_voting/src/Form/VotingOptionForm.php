<?php

namespace Drupal\simple_voting\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for adding and editing VotingOption entities.
 */
class VotingOptionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // If a voting_question was passed via the route (add-form path), pre-fill
    // the question_id field and hide it — no need to select it again.
    $route_match = \Drupal::routeMatch();
    $voting_question = $route_match->getParameter('voting_question');
    if ($voting_question && $this->entity->isNew()) {
      $this->entity->set('question_id', $voting_question);
    }

    $form = parent::buildForm($form, $form_state);

    // Hide the question_id widget when it's already determined by context.
    if ($voting_question) {
      $form['question_id']['#access'] = FALSE;

      // Resolve the question entity (route parameter can be an int or entity).
      $question_entity = is_object($voting_question)
        ? $voting_question
        : \Drupal::entityTypeManager()->getStorage('voting_question')->load($voting_question);

      if ($question_entity) {
        $form['question_context'] = [
          '#markup' => '<div class="voting-option-question-context">'
            . '<span class="voting-option-question-label">' . $this->t('Pergunta') . ':</span> '
            . '<span class="voting-option-question-title">' . htmlspecialchars($question_entity->label(), ENT_QUOTES) . '</span>'
            . '</div>',
          '#weight' => -20,
        ];
      }
    }
    // On edit form, show the question title from the already-set entity value.
    elseif (!$this->entity->isNew()) {
      $question_entity = $this->entity->get('question_id')->entity;
      if ($question_entity) {
        $form['question_context'] = [
          '#markup' => '<div class="voting-option-question-context">'
            . '<span class="voting-option-question-label">' . $this->t('Pergunta') . ':</span> '
            . '<span class="voting-option-question-title">' . htmlspecialchars($question_entity->label(), ENT_QUOTES) . '</span>'
            . '</div>',
          '#weight' => -20,
        ];
        $form['question_id']['#access'] = FALSE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);
    $entity = $this->entity;

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Opção %title criada com sucesso.', ['%title' => $entity->label()]));
    }
    else {
      $this->messenger()->addStatus($this->t('Opção %title atualizada com sucesso.', ['%title' => $entity->label()]));
    }

    // Redirect back to the question's options listing page.
    $question_id = $entity->get('question_id')->target_id;
    $form_state->setRedirect('simple_voting.question_options', ['voting_question' => $question_id]);
    return $status;
  }

}
