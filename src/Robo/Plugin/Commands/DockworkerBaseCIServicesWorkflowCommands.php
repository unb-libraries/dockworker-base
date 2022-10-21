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
    $workflow_type = getGitHubActionsWorkflowType();
    $workflow_source = getGitHubActionsWorkflowSource();

    $this->CIServicesWorkflowSourcePath = $this->constructRepoPathString([
      "vendor/unb-libraries/$workflow_source/data/gh-actions",
      "$workflow_type.yaml",
    ]);

    $this->CIServicesWorkflowFilepath = $this->constructRepoPathString([
      '.github/workflows',
      'deployment-workflow.yaml',
    ]);
    $this->writeApplicationCIServicesWorkflowFile();
  }

  /**
   * Writes out the CI Services workflow file.
   */
  protected function writeApplicationCIServicesWorkflowFile() {
    // Set Name.
    $this->setInstanceName();
    $tokenized_workflow_contents = file_get_contents($this->CIServicesWorkflowSourcePath);
    $workflow_contents = str_replace('INSTANCE_NAME', $this->instanceName, $tokenized_workflow_contents);

    // Set Branches.
    $deploy_branches = $this->getDeployableEnvironments();
    $deploy_branches_string = '"' . implode('","', $deploy_branches) . '"';
    $workflow_contents = str_replace('INSTANCE_DEPLOY_BRANCHES', $deploy_branches_string, $workflow_contents);

    // Write File.
    file_put_contents($this->CIServicesWorkflowFilepath, $workflow_contents);
    $this->say('The updated GitHub actions workflow file has been written.');
  }

}
