<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerDockerImageBuildCommands;

/**
 * Defines the commands used to build and push a docker image.
 */
class DockworkerDockerImageDeployCommands extends DockworkerDockerImagePushCommands {

  use KubernetesDeploymentTrait;

  /**
   * Builds this application's docker image, pushes it to the container registry, and updates its k8s deployment with it.
   *
   * @param string $env
   *   The environment to target.
   * @param string[] $options
   *   The array of available CLI options.
   *
   * @option $use-tag
   *   Skip building and deploy with the specified tag.
   *
   * @command docker:image:deploy
   * @throws \Exception
   *
   * @usage prod
   *
   * @dockerimage
   * @dockerpush
   */
  public function buildPushDeployEnv($env, array $options = ['use-tag' => '']) {
    $this->pushCommandInit($env);
    if (empty($options['use-tag'])) {
      $timestamp = date('YmdHis');
      $this->buildPushEnv($env, $timestamp);

      if ($this->dockerImageTagDateStamp) {
        $image_name = "{$this->dockerImageName}:$env-$timestamp";
      }
      else {
        $image_name = "{$this->dockerImageName}:$env";
      }
    }
    else {
      $image_name = "{$this->dockerImageName}:{$options['use-tag']}";
    }

    $this->say('Updating deployment configuration..');
    $deployment_file = $this->applyKubeDeploymentUpdate($this->repoRoot, $env, $image_name);

    $cron_file = static::getKubernetesFileNameFromBranch($this->repoRoot, $env, 'cronjob');
    if (file_exists($cron_file)) {
      $this->say('Updating cron configuration..');
      $cron_file = $this->getTokenizedKubeFile($this->repoRoot, $env, $image_name, 'cronjob');
      $this->setRunOtherCommand("k8s:deployment:delete-apply $cron_file");
    }

    $backup_file = static::getKubernetesFileNameFromBranch($this->repoRoot, $env, 'backup');
    if (file_exists($backup_file)) {
      $this->say('Updating backup configuration..');
      $this->setRunOtherCommand("k8s:deployment:delete-apply $backup_file");
    }

    $testing_file = static::getKubernetesFileNameFromBranch($this->repoRoot, $env, 'testing');
    if (file_exists($testing_file)) {
      $this->say('Updating test configuration..');
      $this->setRunOtherCommand("k8s:deployment:create-test-secrets");
      $this->setRunOtherCommand("k8s:deployment:delete-apply $testing_file");
    }

    $this->say('Checking for successful deployment..');
    $deploy_namespace = static::getKubernetesDeploymentFileNamespace($deployment_file);
    $this->setRunOtherCommand("k8s:deployment:status $deploy_namespace");
  }

}
