<?php

return [

    /**
     * Path or name of the git executable
     */
    'git_bin' => 'git',

    /**
     * Which version to bump by default
     * Can be 'patch', 'minor', 'major'
     */
    'default_version_bump' => 'patch',

    /**
     * Name of the git remote where we need to push (default to 'origin')
     */
    'git_remote_name' => 'origin',

    /**
     * Push to origin default
     */
    'push_to_origin' => true,

    /**
     * Push also tags
     */
    'push_tags' => true,
];
