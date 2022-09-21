<?php

namespace Dockworker\Robo\Plugin\Commands;

use Dockworker\KubernetesDeploymentTrait;
use Dockworker\Robo\Plugin\Commands\DockworkerBaseCommands;

/**
 * Defines a class to write a standardized CI workflow file to a repository.
 */
class DockworkerBaseCIServicesWorkflowCommands extends DockworkerBaseCommands {

  use KubernetesDeploymentTrait;

  protected $CIServicesWorkflowFilepath;
  protected $CIServicesWorkflowSourcePath;

  /**
   * Writes standardized CI Services workflow files for this application to this repository.
   *
   * @command ci:workflow:file:write
   * @aliases update-ci-workflow
   * @aliases uciw
   */
  public function setApplicationCIServicesWorkflowFile() {
    foreach ($this->getCiServicesWorkflowFileDefinitions() as $workflow) {
      $this->CIServicesWorkflowSourcePath = $this->constructRepoPathString([
        $workflow['source_path'],
        $workflow['file_name'],
      ]);
      $this->CIServicesWorkflowFilepath = $this->constructRepoPathString([
        $workflow['repo_path'],
        $workflow['file_name'],
      ]);
      $this->writeApplicationCIServicesWorkflowFile();
    }
  }

  /**
   * Defines which CI Services workflow files exist for this application.
   *
   * @return string[][]
   *   An associative array of workflow files supporting this application.
   */
  protected function getCiServicesWorkflowFileDefinitions() : array {
    return [];
  }

  /**
   * Writes out the CI Services workflow file.
   */
  protected function writeApplicationCIServicesWorkflowFile() {
    $this->setInstanceName();
    $tokenized_workflow_contents = file_get_contents($this->CIServicesWorkflowSourcePath);
    $workflow_contents = str_replace('INSTANCE_NAME', $this->instanceName, $tokenized_workflow_contents);
    $deployable_env_string = '';
    foreach ($this->getDeployableEnvironments() as $deploy_env) {
      $deployable_env_string .= "      refs/heads/$deploy_env\n";
    }
    $workflow_contents = str_replace('DEPLOY_BRANCHES', rtrim($deployable_env_string), $workflow_contents);
    file_put_contents($this->CIServicesWorkflowFilepath, $workflow_contents);
    $this->say('The updated GitHub actions workflow file has been written.');
  }

}
