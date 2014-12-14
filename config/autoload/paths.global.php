<?php
return [
    'paths' => [
        'home_dir'            => $_SERVER['HOME'] . '/.dnsmasq-mgmt',
        'dnsmasq_config_file' => '/usr/local/etc/dnsmasq.d/100-dnsmasq-mgmt.conf',
        'resolver_dir'        => '/etc/resolver',
    ],
];
