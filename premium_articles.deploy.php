<?php

use Drupal\Core\Config\Config;

/**
 * Migrate overviews to Entity Overview
 */
function premium_articles_deploy_migrate_entity_overview() {
  /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
  $module_handler = \Drupal::service('module_handler');
  if ($module_handler->moduleExists('entity_overview')) {
    $config_factory = \Drupal::configFactory();
    $list = $config_factory->listAll('premium_articles.');
    foreach ($list as $config_id) {
      $old_config = $config_factory->getEditable($config_id);
      if (!$old_config->isNew()) {
        $new_config = $config_factory->getEditable(str_replace('premium_articles', 'entity_overview', $config_id));
        foreach ($old_config->getRawData() as $key => $value) {
          $new_config->set($key, $value);
        }
        $new_config->save();
        $old_config->delete();
      }
    }

    $config_factory = \Drupal::configFactory();
    $list = $config_factory->listAll('core.entity_view_display.');
    foreach ($list as $config_id) {
      $config = $config_factory->getEditable($config_id);
      $dependencies = $config->get('dependencies');
      if (isset($dependencies['module']) && in_array('premium_articles', $dependencies['module'])) {
        foreach ($dependencies['module'] as $key => $module) {
          if ($module == 'premium_articles') {
            $dependencies['module'][$key] = 'entity_overview';
          }
        }
        $config->set('dependencies', $dependencies);
        $config->save();
      }
    }

    $list = $config_factory->listAll('field.storage.');
    foreach ($list as $config_id) {
      $config = $config_factory->getEditable($config_id);
      $dependencies = $config->get('dependencies');
      if (isset($dependencies['module']) && in_array('premium_articles', $dependencies['module'])) {
        foreach ($dependencies['module'] as $key => $module) {
          if ($module == 'premium_articles') {
            $dependencies['module'][$key] = 'entity_overview';
          }
        }
        $config->set('dependencies', $dependencies);
        $config->set('module', 'entity_overview');
        $config->set('type', 'overview_filter');

        $config->save();
      }
    }

    $list = $config_factory->listAll('field.field.');
    foreach ($list as $config_id) {
      $config = $config_factory->getEditable($config_id);
      $dependencies = $config->get('dependencies');
      if (isset($dependencies['module']) && in_array('premium_articles', $dependencies['module'])) {
        foreach ($dependencies['module'] as $key => $module) {
          if ($module == 'premium_articles') {
            $dependencies['module'][$key] = 'entity_overview';
          }
        }
        $config->set('dependencies', $dependencies);
        $config->set('field_type', 'overview_filter');

        $config->save();
      }
    }
    return t('Migrated to Entity Overview');
  }
}
