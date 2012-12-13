<?php




//  POST FEED FUNCTIONS

function rssmi_import_feed_post() {
	
	$post_options = get_option('rss_post_options');
	
	if($post_options['active']==1){
		wp_rss_multi_importer_post();
	}
}




add_action('wp_ajax_fetch_now', 'fetch_rss_callback');

function fetch_rss_callback() {

	$post_options = get_option('rss_post_options');

		if($post_options['active']==1){

			wp_rss_multi_importer_post();
	        echo '<h3>The most recent feeds have been put into posts.</h3>';

		}else{
			
	 		echo '<h3>Nothing was done because you have not activated this service.</h3>';
}

	die(); 
}


function filter_id_callback($val) {
    if ($val != null){
	return true;
}
}

function get_values_for_id_keys($mapping, $keys) {
    foreach($keys as $key) {
        $output_arr[] = $mapping[$key];
    }
    return $output_arr;
}


function strip_qs_var($sourcestr,$url,$key){
	if (strpos($url,$sourcestr)>0){
		return preg_replace( '/('.$key.'=.*?)&/', '', $url );
	}else{
		return $url;
	}		
}






function wp_rss_multi_importer_post(){
	


if(!function_exists("wprssmi_hourly_feed")) {
function wprssmi_hourly_feed() { return 0; }  // no caching of RSS feed
}
add_filter( 'wp_feed_cache_transient_lifetime', 'wprssmi_hourly_feed' );





	
  
   	$options = get_option('rss_import_options','option not found');
	$option_items = get_option('rss_import_items','option not found');
	$post_options = get_option('rss_post_options', 'option not found');
	
	


	if ($option_items==false) return "You need to set up the WP RSS Multi Importer Plugin before any results will show here.  Just go into the <a href='/wp-admin/options-general.php?page=wp_rss_multi_importer_admin'>settings panel</a> and put in some RSS feeds";


if(!empty($option_items)){
$cat_array = preg_grep("^feed_cat_^", array_keys($option_items));

	if (count($cat_array)==0) {  // for backward compatibility
		$noExistCat=1;
	}else{
		$noExistCat=0;	
	}

}

    
   if(!empty($option_items)){
	
//GET PARAMETERS  
$size = count($option_items);
$sortDir=0;  // 1 is ascending
$maxperPage=$options['maxperPage'];
$addSource=$post_options['addSource'];
$maxposts=$post_options['maxfeed'];
$post_status=$post_options['post_status'];
$addAuthor=$post_options['addAuthor'];
$bloguserid=$post_options['bloguserid'];

//$thisCategory=$post_options['category'];


//if (!isset($post_options['category'])){
//	$thisCategory=0;
//}
//$catArray=array($thisCategory);  //this is the plugin categories




$wpcatids=array_filter($post_options['categoryid']['wpcatid'],'filter_id_callback');





if (!empty($wpcatids)){
	$catArray = get_values_for_id_keys($post_options['categoryid']['plugcatid'], array_keys($wpcatids));  //orig
//	$catArray=array_filter($post_options['categoryid']['plugcatid'],'filter_id_callback');
}else{
	$catArray=array(0);
	
}

//echo var_dump($catArray);
//exit;





$targetWindow=$post_options['targetWindow'];  // 0=LB, 1=same, 2=new

if(empty($options['sourcename'])){
	$attribution='';
}else{
	$attribution=$options['sourcename'].': ';
}

global $maximgwidth;
$maximgwidth=$post_options['maximgwidth'];;
$descNum=$post_options['descnum'];
$stripAll=$post_options['stripAll'];
$maxperfetch=$post_options['maxperfetch'];
$showsocial=$post_options['showsocial'];
$overridedate=$post_options['overridedate'];
$adjustImageSize=1;
$noFollow=0;
$floatType=1;

if ($floatType=='1'){
	$float="left";
}else{
	$float="none";	
}

   for ($i=1;$i<=$size;$i=$i+1){

	

   			$key =key($option_items);
				if ( !strpos( $key, '_' ) > 0 ) continue; //this makes sure only feeds are included here...everything else are options
				
   			$rssName= $option_items[$key];

   
   			next($option_items);
   			
   			$key =key($option_items);
   			
   			$rssURL=$option_items[$key];



  	next($option_items);
	$key =key($option_items);
	

$rssCatID=$option_items[$key]; 


if (((!in_array(0, $catArray ) && in_array($option_items[$key], $catArray ))) || in_array(0, $catArray ) || $noExistCat==1) {  //makes sure only desired categories are included


	$myfeeds[] = array("FeedName"=>$rssName,"FeedURL"=>$rssURL,"FeedCatID"=>$rssCatID); //with Feed Category ID


}
   
$cat_array = preg_grep("^feed_cat_^", array_keys($option_items));  // for backward compatibility

	if (count($cat_array)>0) {

  next($option_items); //skip feed category
}

   }

  if ($maxposts=="") return "One more step...go into the the <a href='/wp-admin/options-general.php?page=wp_rss_multi_importer_admin&tab=setting_options'>Settings Panel and choose Options.</a>";  // check to confirm they set options

if (empty($myfeeds)){
	
	return "You've either entered a category ID that doesn't exist or have no feeds configured for this category.  Edit the shortcode on this page with a category ID that exists, or <a href=".$cat_options_url.">go here and and get an ID</a> that does exist in your admin panel.";
	exit;
}



 
 foreach($myfeeds as $feeditem){


	$url=(string)($feeditem["FeedURL"]);

	
	while ( stristr($url, 'http') != $url )
		$url = substr($url, 1);


				$feed = fetch_feed($url);

	
	

	if (is_wp_error( $feed ) ) {
		
		if ($size<4){
			return "You have one feed and it's not valid.  This is likely a problem with the source of the RSS feed.  Contact our support forum for help.";
			exit;

		}else{
	//echo $feed->get_error_message();	
		continue;
		}
	}

	$maxfeed= $feed->get_item_quantity(0);  
	
	
	if ($feedAuthor = $feed->get_author())
	{
		$feedAuthor=$feed->get_author()->get_name();
	}




	//SORT DEPENDING ON SETTINGS

		if($sortDir==1){

			for ($i=$maxfeed-1;$i>=$maxfeed-$maxposts;$i--){
				$item = $feed->get_item($i);
				 if (empty($item))	continue;




							if ($enclosure = $item->get_enclosure()){
								if(!IS_NULL($item->get_enclosure()->get_thumbnail())){			
									$mediaImage=$item->get_enclosure()->get_thumbnail();
								}else if (!IS_NULL($item->get_enclosure()->get_link())){
									$mediaImage=$item->get_enclosure()->get_link();	
								}
							}


							if ($itemAuthor = $item->get_author())
							{
								$itemAuthor=$item->get_author()->get_name();
							}else if (!IS_NULL($feedAuthor)){
								$itemAuthor=$feedAuthor;

							}



				$myarray[] = array("mystrdate"=>strtotime($item->get_date()),"mytitle"=>$item->get_title(),"mylink"=>$item->get_link(),"myGroup"=>$feeditem["FeedName"],"mydesc"=>$item->get_content(),"myimage"=>$mediaImage,"mycatid"=>$feeditem["FeedCatID"],"myAuthor"=>$itemAuthor);

							unset($mediaImage);
							unset($itemAuthor);

				}

			}else{	

			for ($i=0;$i<=$maxposts-1;$i++){
					$item = $feed->get_item($i);
					if (empty($item))	continue;	


				if ($enclosure = $item->get_enclosure()){

					if(!IS_NULL($item->get_enclosure()->get_thumbnail())){			
						$mediaImage=$item->get_enclosure()->get_thumbnail();
					}else if (!IS_NULL($item->get_enclosure()->get_link())){
						$mediaImage=$item->get_enclosure()->get_link();	
					}	
				}


				if ($itemAuthor = $item->get_author())
				{
					$itemAuthor=$item->get_author()->get_name();
				}else if (!IS_NULL($feedAuthor)){
					$itemAuthor=$feedAuthor;

				}



				$myarray[] = array("mystrdate"=>strtotime($item->get_date()),"mytitle"=>$item->get_title(),"mylink"=>$item->get_link(),"myGroup"=>$feeditem["FeedName"],"mydesc"=>$item->get_content(),"myimage"=>$mediaImage,"mycatid"=>$feeditem["FeedCatID"],"myAuthor"=>$itemAuthor);


							unset($mediaImage);
							unset($itemAuthor);
					}	
			}


		}





//  CHECK $myarray BEFORE DOING ANYTHING ELSE //

if ($dumpthis==1){
	var_dump($myarray);
}
if (!isset($myarray) || empty($myarray)){
	
	return "There is a problem with the feeds you entered.  Go to our <a href='http://www.allenweiss.com/wp_plugin'>support page</a> and we'll help you diagnose the problem.";
		exit;
}





//$myarrary sorted by mystrdate

foreach ($myarray as $key => $row) {
    $dates[$key]  = $row["mystrdate"]; 
}



//SORT, DEPENDING ON SETTINGS

if($sortDir==1){
	array_multisort($dates, SORT_ASC, $myarray);
}else{
	array_multisort($dates, SORT_DESC, $myarray);		
}



if($targetWindow==0){
	$openWindow='class="colorbox"';
}elseif ($targetWindow==1){
	$openWindow='target=_self';		
}else{
	$openWindow='target=_blank ';	
}

	$total=0;

global $wpdb;
foreach($myarray as $items) {
	
	$total = $total +1;
	if ($total>$maxperfetch) break;
	$thisLink=trim($items["mylink"]);
	
	
	
	//  YouTube  //  NEEDS WORK
	if ($targetWindow==0 && strpos($items["mylink"],'www.youtube.com')>0){
		

		if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $items["mylink"], $match)) {
			
		    $video_id = $match[1];
			$items["mylink"]='http://www.youtube.com/embed/'.$video_id.'?rel=0&amp;wmode=transparent';
		
			$openWindow='class="rssmi_youtube"';
			global $YTmatch;
			$YTmatch=1;
		}
	}
	
	
	
	
	
	
	
	
	
	$thisLink = strip_qs_var('bing.com',$thisLink,'tid');  // clean time based links from Bing
	

	
	$mypostids = $wpdb->get_results("select * from $wpdb->postmeta where meta_value='$thisLink'");
	$thisContent='';
	
if (empty( $mypostids )){  //only post if it hasn't been posted before
	

	
  	$post = array();  
  	$post['post_status'] = $post_status;

if ($overridedate==1){
	$post['post_date'] = date("Y-m-d H:i:s", time());  
}else{
  	$post['post_date'] = date('Y-m-d H:i:s',$items['mystrdate']);
}


  	$post['post_title'] = trim($items["mytitle"]);

$authorPrep="By ";

		if(!empty($items["myAuthor"]) && $addAuthor==1){
		 $thisContent .=  '<span style="font-style:italic; font-size:16px;">'.$authorPrep.' <a '.$openWindow.' href='.$items["mylink"].' '.($noFollow==1 ? 'rel=nofollow':'').'">'.$items["myAuthor"].'</a></span>';  
			}

	
	$thisContent .= showexcerpt($items["mydesc"],$descNum,$openWindow,$stripAll,$items["mylink"],$adjustImageSize,$float,$noFollow,$items["myimage"]);

	if ($addSource==1){
	$thisContent .= ' <br>Source: <a href='.$items["mylink"].'  '.$openWindow.'>'.$items["myGroup"].'</a>';
	}

	
	
	
	
	
	if ($showsocial==1){
	$thisContent .= '<span style="margin-left:10px;"><a href="http://www.facebook.com/sharer/sharer.php?u='.$items["mylink"].'"><img src="'.WP_RSS_MULTI_IMAGES.'facebook.png"/></a>&nbsp;&nbsp;<a href="http://twitter.com/intent/tweet?text='.rawurlencode($items["mytitle"]).'%20'.$items["mylink"].'"><img src="'.WP_RSS_MULTI_IMAGES.'twitter.png"/></a></span>';
	
	}
  	$post['post_content'] = $thisContent;

	$mycatid=$items["mycatid"];



	$catkey=array_search($mycatid, $post_options['categoryid']['plugcatid']);
	
	
	$blogcatid=array($post_options['categoryid']['wpcatid'][$catkey]);

	
	$post['post_category'] =$blogcatid;
	
	$post['post_author'] =$bloguserid;


  	$post_id = wp_insert_post($post);
	add_post_meta($post_id, 'rssmi_source_link', $thisLink);
				//wp_set_post_terms( $post_id, $terms, $taxonomy, $append )
	unset($post);
}


}

}


  }

?>