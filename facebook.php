<?php

//get url var containing Facebook Group ID
$guid = $_GET['guid'];
//get the number of posts to display in feed
$limit = $_GET['posts'];

//if no guid given, defaults to Lounge Pirata
$guid = (strlen($guid)>0)?$guid:'177906248889471';
$facebookFeed =  'http://graph.facebook.com/'.$guid.'/feed';

//get the feed
$json = file_get_contents($facebookFeed);

//parse Json format into PHP array
$data = json_decode($json);
if ($_GET['dump'] == true) var_dump($data);

//if no feed title and desctiption given, defaults to Lounge Pirata
$feedTitle = (isset($_GET['feedTitle']))? $_GET['feedTitle']:'Lounge Pirata RSS';
$feedDesc = (isset($_GET['feedDesc']))? $_GET['feedDesc']:'Feed RSS do grupo Lounge Pirata no Facebook';

//start constructing the xml rss feed
// xmlns:media="http://search.yahoo.com/mrss/"
$xmlresult= '<?xml version="1.0" encoding="ISO-8859-1"?>
<rss version="2.0">
<channel>
	<title>'.$feedTitle.'</title> 
	<description>'.$feedDesc.'</description> 
	<link>'.htmlentities('http://www.facebook.com/home.php?sk=group_'.$guid, ENT_QUOTES,"ISO-8859-1").'</link>';
//<atom:link href="http://www.debatevisual.com/code/facebook.php" rel="self" type="application/rss+xml" />';

//if no posts limit given, use all
$limit = (strlen($limit)>0)? $limit : count($data->data)-1;

//$limit=9;
//loop posts
for ($i=0; $i<$limit;$i++){
	//get values from json array
	$username = $data->data[$i]->from->name;
	$message = $data->data[$i]->message;
	$pictureUrl = $data->data[$i]->picture;
	$link = $data->data[$i]->link;
	$name = $data->data[$i]->name;
	$description = $data->data[$i]->description;
	$caption = $data->data[$i]->caption;
	$created_time = $data->data[$i]->created_time;
	$id = $data->data[$i]->id;
	$likes = $data->data[$i]->likes->data;

	//loop to get comments
	$comments = '';
	if (count($data->data[$i]->comments->data)>0){
		$comments = '<div style="margin:10px 0 0 20px;display:block;padding:5px;background-color:#f7f7f7;">Last Comments:';
		$j=0;
		while ($j<count($data->data[$i]->comments->data)){
			$commentName = $data->data[$i]->comments->data[$j]->from->name;
			$commentMessage = $data->data[$i]->comments->data[$j]->message;
			$commentCreated_time = $data->data[$i]->comments->data[$j]->created_time;
			$comments .= '<br /><b>'.$commentName.'</b> - '.substr(strftime("%a, %d %b %Y %H:%M:%S %z",tstamptotime($commentCreated_time)),5,17).'<br />- '.$commentMessage.'<br />';
			$j++;
		}
		$comments .= '</div>';
	}
	
	//formats picture url into html
	$picture = '';
	if (strlen($pictureUrl)>0)$picture = '<a href="'.$link.'" target="_blank"><img alt="" border="0" src="'.utf8_decode($pictureUrl).'"  align="left" style="padding-right:5px;"/></a>';
	
	$likes = (count($likes)>1)? $likes = '<i>'.count($likes).' people like this</i>' : $likes = (count($likes)>0)? $likes = '<i>'.count($likes).' person likes this</i>' : '';;
	
	
	//transforms all info into html formatting
	$message = utf8_decode(($message));
	if (strlen($description)>0 && strlen($message)>0) $message .= '<br />';
	if (strlen($name)>0) {
		$message .= '<div style="display:block;padding-top:5px;">'.$picture;
		if (strlen($link)>0) $message .= '<a href="'.$link.'" target="_blank"><b>'.utf8_decode($name).'</b></a>
		
		<br /><i>'.$caption.'</i>
		
		<br />'.utf8_decode(($description));
		$message .='<div style="clear:both"></div>'.$likes.'</div>';	
	}
	
	//convert facebook create_time format into RFC 822 rss compatible format
	$pubDate = strftime("%a, %d %b %Y %H:%M:%S %z",tstamptotime($created_time));
	
	//if there's a link, encode it into html format
	$link = (strlen($link)>0)? '<link>'.htmlentities($link, ENT_QUOTES,"ISO-8859-1").'</link>': '';
	
	//rss item
	$rssitem = 	 '
	<item>
		<title>'.utf8_decode($username).' - '.substr($pubDate,5,17).'</title> 
		<pubDate>'.$pubDate.'</pubDate>
		'.$link.'
		<description><![CDATA['.$message.utf8_decode(nl2br($comments)).']]></description>
		<guid isPermaLink="false">'.$id.'</guid>
	</item>';
				//'<media:content url="'.$pictureUrl.'" medium="image" />
	$xmlresult .= $rssitem;
}

//end rss xml structure
$xmlresult .= '
	</channel>
</rss>';

//print rss xml
header('Content-Type: application/xml; charset=iso-8859-1');
print $xmlresult;

function tstamptotime($tstamp) {
        // converts ISODATE to unix date
        // 1984-09-01T14:21:31Z
       sscanf($tstamp,"%u-%u-%uT%u:%u:%uZ",$year,$month,$day,
        $hour,$min,$sec);
        $newtstamp=mktime($hour,$min,$sec,$month,$day,$year);
        return $newtstamp;
    }        

?>