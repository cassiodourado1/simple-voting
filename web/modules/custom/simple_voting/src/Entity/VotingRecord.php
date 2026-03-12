<?php

namespace Drupal\simple_voting\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Voting Record entity.
 *
 * Stores each individual vote cast by a user.
 * A unique key on (question_id, user_id) at the database level guarantees
 * that a user can vote only once per question, even under concurrent requests.
 *
 * @ContentEntityType(
 *   id = "voting_record",
 *   label = @Translation("Voting Record"),
 *   label_collection = @Translation("Voting Records"),
 *   label_singular = @Translation("voting record"),
 *   label_plural = @Translation("voting records"),
 *   label_count = @PluralTranslation(
 *     singular = "@count voting record",
 *     plural = "@count voting records",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *   },
 *   base_table = "voting_record",
 *   admin_permission = "administer voting questions",
 *   entity_keys = {
 *     "id"   = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class VotingRecord extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    // Gera os campos base: id e uuid.
    $fields = parent::baseFieldDefinitions($entity_type);

    // Referência à pergunta votada.
    $fields['question_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Question'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'voting_question');

    // Referência à opção escolhida.
    $fields['option_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Option'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'voting_option');

    // Referência ao usuário que votou.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user');

    // Origem do voto: 'cms' ou 'api'.
    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source'))
      ->setDescription(t('Where the vote came from: "cms" or "api".'))
      ->setSetting('max_length', 10)
      ->setDefaultValue('cms');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
