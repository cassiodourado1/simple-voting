<?php

namespace Drupal\simple_voting\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Voting Question entity.
 *
 * @ContentEntityType(
 *   id = "voting_question",
 *   label = @Translation("Voting Question"),
 *   label_collection = @Translation("Voting Questions"),
 *   label_singular = @Translation("voting question"),
 *   label_plural = @Translation("voting questions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count voting question",
 *     plural = "@count voting questions",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "list_builder" = "Drupal\simple_voting\Entity\VotingQuestionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\simple_voting\Form\VotingQuestionForm",
 *       "add"     = "Drupal\simple_voting\Form\VotingQuestionForm",
 *       "edit"    = "Drupal\simple_voting\Form\VotingQuestionForm",
 *       "delete"  = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "voting_question",
 *   admin_permission = "administer voting questions",
 *   entity_keys = {
 *     "id"     = "id",
 *     "uuid"   = "uuid",
 *     "label"  = "title",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical"   = "/admin/simple-voting/questions/{voting_question}",
 *     "add-form"    = "/admin/simple-voting/questions/add",
 *     "edit-form"   = "/admin/simple-voting/questions/{voting_question}/edit",
 *     "delete-form" = "/admin/simple-voting/questions/{voting_question}/delete",
 *     "collection"  = "/admin/simple-voting/questions",
 *   },
 * )
 */
class VotingQuestion extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    // Gera os campos base: id e uuid.
    $fields = parent::baseFieldDefinitions($entity_type);

    // Título da pergunta.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Se a pergunta está ativa para receber votos.
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Whether this question is open for voting.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 10])
      ->setDisplayConfigurable('form', TRUE);

    // Se o total de votos deve ser exibido após votar.
    $fields['show_results'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show results after vote'))
      ->setDescription(t('Whether to display vote totals after the user votes.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
