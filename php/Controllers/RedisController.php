<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 12/10/2016
 * Time: 21:13
 */

namespace DirectAdmin\RedisManagement\Controllers;

class RedisController
{
    private $_config           = array();
    private $_instances        = array();
    private $_basePath         = NULL;
    private $_nextInstancePort = NULL;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Init
     *
     * @return void
     */
    public function init()
    {
        $this->_basePath = dirname(dirname(__DIR__));
        $this->_config   = require_once($this->_basePath.'/php/Config/main.php');

        if($this->_config)
        {
            // if local config exists, merge it with default config
            if(file_exists($this->_basePath.'/php/Config/local.php'))
            {
                $localConfig = require_once($this->_basePath.'/php/Config/local.php');

                $this->_config = array_replace_recursive($this->_config, $localConfig);
            }

            $this->_nextInstancePort = $this->_config['plugin']['startPort'];

            if (file_exists($this->_basePath . '/' . $this->_config['plugin']['dataFile']))
            {
                $jsonContent = file_get_contents($this->_basePath . '/' . $this->_config['plugin']['dataFile']);

                if (@json_decode($jsonContent))
                {
                    $json = json_decode($jsonContent, TRUE);

                    if (isset($json['instances']))
                    {
                        $this->_instances = $json['instances'];
                    }

                    if (isset($json['nextInstancePort']))
                    {
                        $this->_nextInstancePort = $json['nextInstancePort'];
                    }
                }
            }
        }
        else
        {
            throw new \Exception('No config data available!');
        }
    }

    /**
     * Get Instances
     *
     * @param null $username
     *
     * @return array
     */
    public function getInstances($username = NULL)
    {
        if ($username)
        {
            if (isset($this->_instances[$username]))
            {
                return $this->_instances[$username];
            }
            else
            {
                return NULL;
            }
        }
        else
        {
            if($this->_instances)
            {
                return $this->_instances;
            }
            else
            {
                return NULL;
            }
        }
    }

    /**
     * Create Instance
     *
     * @param $username
     *
     * @return bool
     */
    public function createInstance($username)
    {
        $password = $this->_generatePassword();
        $port     = $this->_nextInstancePort;

        // add instance
        if ($this->_addInstanceData($username, $port, $password))
        {
            // create instance config
            if ($this->_createInstanceConfig($port, $password))
            {
                // save data
                if ($this->_saveData())
                {
                    // create instance data dir
                    if($this->_createInstanceDataDir($port))
                    {
                        // enable and start service
                        $this->_enableService($port);
                        $this->_startService($port);

                        return TRUE;
                    }
                }
            }
        }

        return FALSE;
    }

    /**
     * Delete Instance
     *
     * @param $username
     * @param $port
     *
     * @return bool
     */
    public function deleteInstance($username, $port)
    {
        $this->_disableService($port);
        $this->_stopService($port);

        if ($this->_deleteInstanceData($username, $port))
        {
            if ($this->_deleteInstanceConfig($port))
            {
                $this->_deleteInstanceDataDir($port);

                // save data
                if ($this->_saveData())
                {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    /**
     * Add Instance Data
     *
     * @param $username
     * @param $port
     * @param $password
     *
     * @return bool
     */
    private function _addInstanceData($username, $port, $password)
    {
        $this->_instances[$username][$port] = array(
            'username' => $username,
            'port'     => $port,
            'password' => $password,
            'created'  => time(),
        );

        $this->_nextInstancePort++;

        return TRUE;
    }

    /**
     * Delete Instance Data
     *
     * @param $username
     * @param $port
     *
     * @return bool
     */
    private function _deleteInstanceData($username, $port)
    {
        if (isset($this->_instances[$username][$port]))
        {
            unset($this->_instances[$username][$port]);

            // if user has no instances anymore, remove entire user segment
            if(!$this->_instances[$username])
            {
                unset($this->_instances[$username]);
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Save
     *
     * @return bool
     */
    private function _saveData()
    {
        // prepare data
        $data = array(
            'instances'        => $this->_instances,
            'nextInstancePort' => $this->_nextInstancePort
        );

        // encode data to json
        $json = json_encode($data);

        // determine data dir path
        $pathInfo = pathinfo($this->_basePath . '/' . $this->_config['plugin']['dataFile']);

        // check if data direcory already exists
        if (!is_dir($pathInfo['dirname']))
        {
            // create data directory
            mkdir($pathInfo['dirname'], 0755, TRUE);
        }

        // save json to file
        if (file_put_contents($this->_basePath . '/' . $this->_config['plugin']['dataFile'], $json))
        {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Create Instance Config
     *
     * @param $port
     * @param $password
     *
     * @return bool
     */
    private function _createInstanceConfig($port, $password)
    {
        // get redis template contents
        if ($templateContent = file_get_contents($this->_basePath . '/php/Templates/redis-instance.conf'))
        {
            // replace variables with actual values
            $replaceTokens = array(
                '{{ port }}',
                '{{ password }}',
                '{{ dataDir }}',
            );
            $replaceValues = array(
                $port,
                $password,
                $this->_config['redis']['dataDir'],
            );
            $configContent = str_replace($replaceTokens, $replaceValues, $templateContent);

            // check if redis instance config dir needs to be created
            if (!is_dir($this->_config['redis']['configDir'].'/'))
            {
                mkdir($this->_config['redis']['configDir'].'/', 0755);
                chown($this->_config['redis']['configDir'].'/', $this->_config['redis']['user']);
                chgrp($this->_config['redis']['configDir'].'/', $this->_config['redis']['user']);
            }

            // save config file
            if (file_put_contents($this->_config['redis']['configDir'] . '/' . $port . '.conf', $configContent))
            {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Delete Instance Config
     *
     * @param $port
     *
     * @return bool
     */
    public function _deleteInstanceConfig($port)
    {
        if (file_exists($this->_config['redis']['configDir'] . '/' . $port . '.conf'))
        {
            unlink($this->_config['redis']['configDir'] . '/' . $port . '.conf');

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Create Instance Data Dir
     *
     * @param $port
     *
     * @return bool
     */
    public function _createInstanceDataDir($port)
    {
        // check if redis data dir needs to be created
        if (!is_dir($this->_config['redis']['dataDir'].'/'))
        {
            mkdir($this->_config['redis']['dataDir'].'/', 0755);
            chown($this->_config['redis']['dataDir'].'/', $this->_config['redis']['user']);
            chgrp($this->_config['redis']['dataDir'].'/', $this->_config['redis']['user']);
        }

        if(mkdir($this->_config['redis']['dataDir'].'/'.$port.'/', 0755))
        {
            chown($this->_config['redis']['dataDir'].'/'.$port.'/', $this->_config['redis']['user']);
            chgrp($this->_config['redis']['dataDir'].'/'.$port.'/', $this->_config['redis']['user']);

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Delete Instance Data Dir
     *
     * @param $port
     *
     * @return bool
     */
    public function _deleteInstanceDataDir($port)
    {
        if(is_dir($this->_config['redis']['dataDir'].'/'.$port))
        {
            if($this->_exec('rm -rf '.$this->_config['redis']['dataDir'].'/'.$port.'/'))
            {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Enable Service
     *
     * @param $port
     *
     * @return bool
     */
    public function _enableService($port)
    {
        return $this->_exec('sudo systemctl enable redis@' . $port);
    }

    /**
     * Disable Service
     *
     * @param $port
     *
     * @return bool
     */
    public function _disableService($port)
    {
        return $this->_exec('sudo systemctl disable redis@' . $port);
    }

    /**
     * Start Service
     *
     * @param $port
     *
     * @return bool
     */
    public function _startService($port)
    {
        return $this->_exec('sudo systemctl start redis@' . $port);
    }

    /**
     * Stop Service
     *
     * @param $port
     *
     * @return bool
     */
    public function _stopService($port)
    {
        return $this->_exec('sudo systemctl stop redis@' . $port);
    }

    /**
     * Exec
     *
     * @param $command
     *
     * @return bool
     */
    public function _exec($command)
    {
        if ($output = shell_exec($command))
        {
            return $output;
        }

        return FALSE;
    }

    /**
     * Generate Password
     *
     * @param int    $length
     * @param bool   $add_dashes
     * @param string $available_sets
     *
     * @return string
     */
    private function _generatePassword($length = 15, $add_dashes = FALSE, $available_sets = 'luds')
    {
        $sets = array();
        if (strpos($available_sets, 'l') !== FALSE)
        {
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        }
        if (strpos($available_sets, 'u') !== FALSE)
        {
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        }
        if (strpos($available_sets, 'd') !== FALSE)
        {
            $sets[] = '23456789';
        }
        if (strpos($available_sets, 's') !== FALSE)
        {
            $sets[] = '!@#$%&*?';
        }
        $all      = '';
        $password = '';
        foreach ($sets as $set)
        {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++)
        {
            $password .= $all[array_rand($all)];
        }
        $password = str_shuffle($password);
        if (!$add_dashes)
        {
            return $password;
        }
        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while (strlen($password) > $dash_len)
        {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;

        return $dash_str;
    }
}