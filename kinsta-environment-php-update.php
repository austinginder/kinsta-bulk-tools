<?php

$environment_id = empty( $args[0] ) ? "" : $args[0];
$php_version    = empty( $args[1] ) ? "" : $args[1];

if ( empty( $php_version ) || empty( $environment_id ) ) {
    return;
}

// Fetch environment
$environment          = json_decode( shell_exec( "php kinsta.php fetch $environment_id" ) );
$domain               = $environment->domain;
$homepage             = "https://$domain?kinsta-cache-cleared=true";
$hash                 = shell_exec( "curl -s $homepage | md5sum | cut -c -32" );
$php_version_original = str_replace( "php", "", $environment->php_version );

if ( $php_version == $php_version_original ) {
    echo "Completed PHP_to_$php_version $environment->domain $environment_id\n";
    return;
}

// Start PHP upgrades
if ( empty( $environment->status ) ) {

    // Perform PHP update respond with environment ID and operation ID
    $response = CaptainCore\Remote\Kinsta::put(
        "sites/tools/modify-php-version", [
                "environment_id" => $environment_id,
                "php_version"    => $php_version
            ]
    );

    if ( ! empty( $response->operation_id ) ) {
        echo "Upgrading PHP_to_$php_version $environment->domain $environment_id $response->operation_id $hash";
    }
    return;

}

if ( $environment->status == "started" ) {
    $response = CaptainCore\Remote\Kinsta::get( "sites/$environment->site_id/environments" );
    foreach( $response->site->environments as $env ) {
        if ( $env->id == $environment->id ) {
            $php_version_check = $env->container_info->php_engine_version;
            $php_version_check = str_replace( "php", "", $php_version_check );
            if ( $php_version_check ==  $php_version ) {
                echo "Completed PHP_to_$php_version $environment->domain $environment_id\n";
                return;
            }
        }
    }

    $response = CaptainCore\Remote\Kinsta::get( "operations/$environment->operation_id" );
    if ( $response->status == "200" ) {
        echo "Completed PHP_to_$php_version $environment->domain $environment_id $hash\n";
        return;
    }
}

if ( $environment->status == "completed" ) {
    if ( $environment->hash != $hash ) {
        // Perform PHP downgrade
        // $response = CaptainCore\Remote\Kinsta::put(
        //     "sites/tools/modify-php-version", [
        //             "environment_id" => $environment_id,
        //             "php_version"    => $php_version_original
        //         ]
        // );
        //if ( ! empty( $response->operation_id ) ) {
        //    echo "Downgrading $environment->domain $environment_id $response->operation_id $hash $environment->hash";
        //}
    } else {
        echo "Done $environment->domain $environment_id $hash";
    }
}

//if ( $environment->status == "downgrading" ) {
//    $response = CaptainCore\Remote\Kinsta::get( "operations/$environment->operation_id" );
//    if ( $response->status == "200" ) {
//        echo "Downgraded $domain $environment_id";
//    }
//}