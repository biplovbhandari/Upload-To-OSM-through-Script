<?php

	//For making changes to Polygon features

	require_once('./updateOSMClass.php');

	$url = "********************";

	$parts = file_get_contents($url);

	$json = json_decode($parts);

	$to_osm = array();


	/* To fetch an object from the API you use the osm class */

	$osm = new osm();

	/* In order to commit changes we can start a changeset, in auto-commit, or in one transaction
		* for structural integrety the atomic method (true) is preferred
	*/

	$changeset = new changeset('username', 'password', false);

	/* Lets make some tags 
		These tags are added as attributes to the changeset element
	*/
								
	$tags = array();

	$changeset->addTag($tags, 'created_by', 'Your Name');
	$changeset->addTag($tags, 'comment', 'Something');

	/* Lets create a quick and simple Changeset */
	$changesetid = $changeset->simpleCreate($tags);	
	echo("ChangesetID = ".$changesetid);


	foreach ($json as $object) {

		$path="";

		$id = $object->osmid;

		unset($_GET);

		$_GET = array();

		$todelete = $osm->getWay($id);

		/* Since we have to update the changeset (and version for updates) an XML parser can be handy */ 
		$xml = new SimpleXMLElement($todelete);


		/*<------------------------------------------------------->*/
					
		$xml->way['changeset'] = $changesetid;
		$way = $xml->xpath('way');

		foreach ($object as $currKey=>$currValue) {

			if (in_array($currKey, $to_osm)){

				$_GET[$currKey] = $currValue;
			}
		}


		/*In order to update the old attributes of the features*/

		$oldTags=array();

		foreach ($_GET as $currKey => $currValue) {

        	$path .= "//tag[@k='" . $currKey . "'] | ";

    	}

    	$path = rtrim($path, " ");
		$path = rtrim($path, "|");

		$oldTags = $xml->xpath('//tag');

		foreach ($oldTags as $tag) {

	        foreach ($_GET as $currKey => $currValue) {

	            if ($tag['k'] == $currKey) {

	                $dom = dom_import_simplexml($tag);
	                $dom->parentNode->removeChild($dom);
	            }
	        }
    	}

    	//Loop to enter new values for 'tag' keys//
	    foreach ($_GET as $currKey => $currValue){

	        $childTag = $way[0]->addChild('tag');
	        $childTag['k'] = $currKey;
	        $childTag['v'] = $currValue;
	    }

	    /* Render the structure back to XML */
		$todelete = $xml->way->asXML();

		/* Now we can use the deleteWay or updateWay function to change an existing way */
		$changeset->modifyWay($id, $todelete);		
	}

	/* This would commit our changeset, or just close it */
    $changeset->close();
?>

