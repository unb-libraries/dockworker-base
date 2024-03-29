<?php

namespace Dockworker;

use Dockworker\DockworkerException;

/**
 * Provides methods to interact with local docker containers.
 */
trait LocalDockerContainerTrait {

  /**
   * Executes a command in a local docker container.
   *
   * @param string $name
   *   The name of the container to execute the command in.
   * @param string $command
   *   The command to execute.
   * @param bool $except_on_error
   *   TRUE to throw an exception on error. FALSE otherwise.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return string
   *   The STDOUT output from the command.
   */
  protected function localDockerContainerExecCommand($name, $command, $except_on_error = TRUE) {
    exec(
      sprintf("docker exec -t %s sh -c '%s'",
        $name,
        $command
      ),
      $cmd_output,
      $return_code
    );
    if ($return_code != 0 && $except_on_error) {
      $msg = implode("\n", $cmd_output);
      throw new DockworkerException("Local docker command [$command] returned error code $return_code : $msg.");
    }
    return $cmd_output;
  }

  /**
   * Checks if the local application is running.
   *
   * @throws \Dockworker\DockworkerException
   */
  public function getLocalRunning() {
    $container_name = $this->instanceName;

    exec(
      "docker inspect -f {{.State.Running}} $container_name 2>&1",
      $output,
      $return_code
    );

    // Check if container exists.
    if ($return_code > 0) {
      throw new DockworkerException(sprintf(self::ERROR_CONTAINER_MISSING, $container_name));
    }

    // Check if container stopped.
    if ($output[0] == "false") {
      throw new DockworkerException(sprintf(self::ERROR_CONTAINER_STOPPED, $container_name));
    }
  }

  /**
   * Copy a file between a docker container and the local filesystem.
   *
   * @param string $source_path
   *   The source path of the file to copy.
   * @param string $target_path
   *   The target path of the file to copy.
   * @param bool $except_on_error
   *   TRUE to throw an exception on error. FALSE otherwise.
   *
   * @throws \Dockworker\DockworkerException
   *
   * @return string
   *   The STDOUT output from the command.
   */
  protected function localDockerContainerCopyCommand($source_path, $target_path, $except_on_error = TRUE) {
    exec(
      sprintf("docker cp %s %s",
        $source_path,
        $target_path
      ),
      $cmd_output,
      $return_code
    );
    if ($return_code != 0 && $except_on_error) {
      throw new DockworkerException("Local copy [$source_path -> $target_path] returned error code $return_code : $cmd_output.");
    }
    return $cmd_output;
  }

}
