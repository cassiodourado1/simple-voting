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
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);
    $entity = $this->entity;

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Option %title created.', ['%title' => $entity->label()]));
    }
    else {
      $this->messenger()->addStatus($this->t('Option %title updated.', ['%title' => $entity->label()]));
    }

    // Redirect back to the parent question's edit page.
    $question_id = $entity->get('question_id')->target_id;
    $form_state->setRedirect('entity.voting_question.edit_form', ['voting_question' => $question_id]);
    return $status;
  }

}
