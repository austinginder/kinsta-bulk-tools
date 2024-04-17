<?php

$action = empty( $argv[1] ) ? "" : $argv[1];
$name   = empty( $argv[2] ) ? "" : $argv[2];
$value  = empty( $argv[3] ) ? "" : $argv[3];

if ( $action == "fetch-environments" ) {

    if ( ! is_file( "data.json" ) ) {
        kinsta_populate_data();
    }

    $environments = file_get_contents( __DIR__ . "/data.json" );
    $environments = json_decode( $environments );
    $environment_ids = [];

    if ( $name == "php" ) {
        foreach ( $environments as $environment ) {
            if ( $environment->php_version == "php$value" ) {
                $environment_ids[] = $environment->id;
            }
        }
    }
    echo implode( " ", $environment_ids );
}

if ( $action == "fetch-environments-downgrade" ) {

    if ( ! is_file( "data.json" ) ) {
        kinsta_populate_data();
    }

    $environments = file_get_contents( __DIR__ . "/data.json" );
    $environments = json_decode( $environments );
    $environment_ids = [];

    foreach ( $environments as $environment ) {
        if ( $environment->php_version == "php$value" ) {
            $environment_ids[] = $environment->id;
        }
    }

    echo implode( " ", $environment_ids );
}

if ( $action == "fetch" ) {
    $environment = (object) [];

    if ( ! is_file( "data.json" ) ) {
        kinsta_populate_data();
    }

    $environments = file_get_contents( __DIR__ . "/data.json" );
    $environments = json_decode( $environments );
    $environment_ids = [];

    foreach ( $environments as $environment ) {
        if ( $environment->id == $name ) {
            $environment_ids[] = $environment->id;
            echo json_encode( $environment );
        }
    }
}

if ( $action == "process-response" ) {

    if ( ! is_file( "response.log" ) ) {
        return;
    }

    $response     = file_get_contents( __DIR__ . "/response.log" );
    $response     = explode( "\n", $response );

    $environments = file_get_contents( __DIR__ . "/data.json" );
    $environments = json_decode( $environments );

    foreach ( $response as $line ) {
        if ( empty( $line ) ) {
            continue;
        }
        $row = explode( " ", $line );
        if ( $row[0] == "Upgrading" ) {
            $environment_id = $row[3];
            foreach( $environments as $environment ) {
                if ( $environment->id == $environment_id ) {
                    $environment->status       = "started";
                    $environment->operation_id = $row[4];
                    $environment->hash         = $row[5];
                }
            }
        }
        if ( $row[0] == "Completed" ) {
            $environment_id = $row[3];
            foreach( $environments as $environment ) {
                if ( $environment->id == $environment_id ) {
                    $environment->status = "completed";
                }
            }
        }
    }

    file_put_contents( "data.json", json_encode( $environments, JSON_PRETTY_PRINT ) );
}

function kinsta_populate_data() {
    // Load environments from payload.json into data.json
    $sites = file_get_contents( __DIR__ . "/payload.json" );
    $sites = json_decode( $sites );
    $environments       = [];
    foreach ( $sites->data->company->sites as $site ) {
        if ( empty( $site->environment ) ) {
            continue;
        }
        $environment_id = $site->environment->id;
        $php_version    = $site->environment->activeContainer->phpEngine;
        $domain         = $site->environment->primaryDomain->name;
        
        $environments[]    = [
            "id"          => $environment_id,
            "site_id"     => $site->id,
            "php_version" => $php_version,
            "domain"      => $domain,
        ];
    }
    file_put_contents( "data.json", json_encode( $environments, JSON_PRETTY_PRINT ) );
}