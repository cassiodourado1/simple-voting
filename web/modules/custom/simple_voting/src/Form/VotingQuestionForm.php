<?php

namespace Drupal\simple_voting\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for adding and editing VotingQuestion entities.
 */
class VotingQuestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);
    $entity = $this->entity;

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Question %title created.', ['%title' => $entity->label()]));
    }
    else {
      $this->messenger()->addStatus($this->t('Question %title updated.', ['%title' => $entity->label()]));
    }

    $form_state->setRedirect('entity.voting_question.collection');
    return $status;
  }

}
