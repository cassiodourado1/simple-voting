<?php

namespace Drupal\simple_voting\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Voting Option entity.
 *
 * Each option belongs to a VotingQuestion and represents one answer choice.
 *
 * @ContentEntityType(
 *   id = "voting_option",
 *   label = @Translation("Opção de Votação"),
 *   label_collection = @Translation("Opções de Votação"),
 *   label_singular = @Translation("opção de votação"),
 *   label_plural = @Translation("opções de votação"),
 *   label_count = @PluralTranslation(
 *     singular = "@count opção de votação",
 *     plural = "@count opções de votação",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "form" = {
 *       "default" = "Drupal\simple_voting\Form\VotingOptionForm",
 *       "add"     = "Drupal\simple_voting\Form\VotingOptionForm",
 *       "edit"    = "Drupal\simple_voting\Form\VotingOptionForm",
 *       "delete"  = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "voting_option",
 *   admin_permission = "administer voting questions",
 *   entity_keys = {
 *     "id"    = "id",
 *     "uuid"  = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "add-form"    = "/admin/simple-voting/questions/{voting_question}/options/add",
 *     "edit-form"   = "/admin/simple-voting/options/{voting_option}/edit",
 *     "delete-form" = "/admin/simple-voting/options/{voting_option}/delete",
 *   },
 * )
 */
class VotingOption extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    // Gera os campos base: id e uuid.
    $fields = parent::baseFieldDefinitions($entity_type);

    // Referência à pergunta que esta opção pertence.
    $fields['question_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Pergunta'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'voting_question')
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    // Título da opção (ex: "Sim", "Não", "Candidato A").
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Descrição curta opcional da opção.
    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descrição'))
      ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Imagem opcional da opção.
    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Imagem'))
      ->setSettings([
        'file_extensions' => 'png jpg jpeg webp',
        'alt_field'       => TRUE,
      ])
      ->setDisplayOptions('form', ['type' => 'image_image', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Peso para ordenação das opções.
    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Peso'))
      ->setDescription(t('Define a ordem de exibição das opções.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
