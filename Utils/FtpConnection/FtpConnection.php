<?php

namespace TradusBundle\Utils\FtpConnection;

use Symfony\Component\Console\Output\Output;

/**
 * Class FtpConnection.
 */
class FtpConnection
{
    /**
     * @var int
     */
    protected $port = 21;

    /**
     * @var bool
     */
    protected $passive = true;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var resource
     */
    protected $connection;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var Output
     */
    protected $output;

    /**
     * FtpConnection constructor.
     *
     * @param $host
     * @param $username
     * @param $password
     */
    public function __construct($host, $username, $password)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Function for connecting to the url defined in the plugin.
     *
     * @throws \Exception
     *   Throws an exception if the connection or authentication fails.
     */
    protected function connect()
    {
        // Try to connect to the given url.
        $this->connection = ftp_connect($this->host, $this->port);
        if (! $this->connection) {
            throw new \Exception("Could not connect to {$this->host} on port {$this->port}.");
        }

        // Try to authenticate using the plugin settings.
        if (! ftp_login($this->connection, $this->username, $this->password)) {
            throw new \Exception("Could not authenticate with username {$this->username}".
                'and password .....');
        }

        // In passive mode, data connections are initiated by the client,
        // rather than by the server.
        ftp_pasv($this->connection, $this->passive);
    }

    /**
     * Function for closing the connection.
     */
    protected function close()
    {
        ftp_close($this->connection);
    }

    /**
     * Function for logging an error.
     *
     * @param $error
     *   The error that we need to log.
     */
    protected function logError($error)
    {
        // TODO LOG ERRORS.
        //\Drupal::logger($this->pluginDefinition['id'])->error($error);
    }

    /**
     * Function for generating a temporary file.
     *
     * @param string $destination
     *   The destination path we're trying to upload to.
     *
     * @return string
     *   Returns a temporary filename.
     */
    protected function getTemporaryFileName($destination)
    {
        $extension = explode('.', $destination);

        return md5($destination).'.'.end($extension);
    }

    /**
     * Function for checking if the file exists.
     *
     * @param string $path
     *   Either an absolute path or valid uri using a schema.
     *
     * @return bool
     *   Returns TRUE if the file exists FALSE if not, the error will be logged.
     */
    protected function localFileExists($path)
    {
        if (file_exists($path)) {
            return true;
        }

        $this->logError("File: $path does not exist.");

        return false;
    }

    /**
     * Function for checking if a remote file exists.
     *
     * @param string $path
     *   The path to the file on the remote location.
     *
     * This function uses ftp_size for checking as it is the lightest method.
     *
     * @return bool
     *   Returns TRUE if a file was found or FALSE when not.
     */
    protected function remoteFileExists($path)
    {
        // Check if we can get a file size.
        if (ftp_size($this->connection, $path) > -1) {
            return true;
        }

        return false;
    }

    public function deleteFile($path)
    {
        if (ftp_delete($this->connection, $path)) {
            return true;
        }

        $this->logError("Could not remove existing file: $path.");

        return false;
    }

    public function moveFile($current_path, $new_path)
    {
        if (ftp_rename($this->connection, $current_path, $new_path)) {
            return true;
        }

        $this->logError("Could not move existing file from: $current_path to $new_path.");

        return false;
    }

    public function uploadFile($local_file, $destination)
    {
        $this->connect();
        $success = false;
        $temporary_file_name = $this->getTemporaryFileName($destination);

        // Check if the local file exists before attempting upload.
        if ($this->localFileExists($local_file)) {

            // Start non blocking upload.
            $upload_response = ftp_nb_put($this->connection, $temporary_file_name, $local_file, FTP_ASCII, FTP_AUTORESUME);

            // Check if the upload has failed if so log it else continue.
            if ($upload_response === FTP_FAILED) {
                $this->logError("Failed to upload temporary file: $temporary_file_name to host.");
            } else {
                while ($upload_response === FTP_MOREDATA) {
                    $upload_response = ftp_nb_continue($this->connection);
                    usleep(50);
                }

                // Check if the $destination file exists; if it does we need to remove it
                // before renaming the temporary file.
                if ($this->remoteFileExists($destination)) {

                    // Try to delete the previous file.
                    if ($this->deleteFile($destination)) {

                        // Rename the file and return the result.
                        $success = $this->moveFile($temporary_file_name, $destination);
                    }
                } else {
                    // Rename the file and return the result.
                    $success = $this->moveFile($temporary_file_name, $destination);
                }
            }
        }

        $this->close();

        return $success;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    /**
     * @param bool $passive
     */
    public function setPassive(bool $passive): void
    {
        $this->passive = $passive;
    }

    public function downloadFile($remote_file)
    {
        // @TODO create this method.
    }
}
