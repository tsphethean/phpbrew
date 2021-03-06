<?php
namespace PhpBrew;
use DOMDocument;


/**
 * parse available downloads
 */
class PhpSource
{
    static function versionCompare($verion1, $verion2)
    {
        if( $verion1 == $verion2 ) {
            return 0;
        }
        return version_compare($verion1, $verion2, '>') ? -1 : 1;
    }

    static function getReleaseManagers()
    {
        return array(
            'stas' => 'Stanislav Malyshev',
            'dsp' => 'David Soria Parra',
        );
    }

    static function getReleaseManagerVersions($id)
    {
        $baseUrl = "http://downloads.php.net/$id/";
        // Create context stream
        $context_array = array('http'=>array('proxy'=>'http://10.23.12.100:8080','request_fulluri'=>true));
        $context = stream_context_create($context_array);
    
        // Use context stream with file_get_contents
        $data = file_get_contents($baseUrl,false,$context);
        
        $dom = new DOMDocument;
        $dom->loadHtml( $html );

        $items = $dom->getElementsByTagName('a');
        $versions = array();
        foreach( $items as $item )
        {
            $href = $item->getAttribute('href');
            if( preg_match('/php-(.*?)\.tar\.bz2$/' , $href , $regs ) ) {
                $version = $regs[1];
                $link = $baseUrl . $href;
                $versions[ 'php-' . $version] = array( 'url' => $link );
            }
        }
        return $versions;
    }

    static function getStableVersions($includeOld = false)
    {
        // reference: http://www.php.net/downloads.php
        //            http://www.php.net/releases/
        $downloadUrls = array(
            'http://www.php.net/downloads.php',
            'http://www.php.net/releases/'
        );
        $phpFilePattern = '/php-(.*?)\.tar\.bz2/';
        $versions = array();

        foreach( $downloadUrls as $downloadUrl ) {
            
            // Create context stream
            $context_array = array('http'=>array('proxy'=>'http://10.23.12.100:8080','request_fulluri'=>true));
            $context = stream_context_create($context_array);
        
            // Use context stream with file_get_contents
            $html = @file_get_contents($downloadUrl,false,$context);
            if( ! $html ) {
                echo "connection eror: $downloadUrl\n";
                continue;
            }

            $baseUrl = 'http://www.php.net/distributions/';
            $dom = new DOMDocument;
            @$dom->loadHtml( $html );
            $items = $dom->getElementsByTagName('a');
            foreach( $items as $item ) {
                $link = $item->getAttribute('href');
                if( preg_match($phpFilePattern, $link, $regs ) ) {
                    if( ! $includeOld && version_compare($regs[1],'5.3.0') < 0 ) {
                        continue;
                    }
                    $version = 'php-' . $regs[1];
                    if( strpos($link, '/') === 0 ) {
                        $link = $baseUrl . $version . '.tar.bz2';
                    }
                    $versions[$version] = array( 'url' => $link );
                }
            }
        }
        uksort( $versions, array('self', 'versionCompare') );

        return $versions;
    }

    static function getSvnVersions()
    {
        //    http://www.php.net/svn.php # svn
        return array(
            'php-svn-head' => array( 'svn' => 'https://svn.php.net/repository/php/php-src/trunk' ),
            'php-svn-5.3' => array( 'svn' => 'https://svn.php.net/repository/php/php-src/branches/PHP_5_3' ),
            'php-svn-5.4' => array( 'svn' => 'https://svn.php.net/repository/php/php-src/branches/PHP_5_4' ),
        );
    }

    static function getSnapshotVersions()
    {
        // http://snaps.php.net/php5.3-201202070630.tar.bz2
    }

    static function getVersionInfo($version, $includeOld = false)
    {
        $versions = self::getStableVersions($includeOld);
        if( isset($versions[$version]) )
            return $versions[ $version ];

        $versions = self::getSvnVersions();
        if( isset($versions[$version]) )
            return $versions[ $version ];

        $managers = self::getReleaseManagers();
        foreach($managers as $id => $fullName) {
            $versions = self::getReleaseManagerVersions($id);
            if( isset($versions[$version]) )
                return $versions[ $version ];
        }
    }

}

