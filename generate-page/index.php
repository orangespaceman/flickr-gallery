<?php
  

  // debug
  debug("starting...");


  // create an initial array to contain all images
  $images = array();



  // archive old page
  debug("archiving old page...");
  if (copy("../index.html", "archive/".date("Y-m-d_H-i-s").".html")) {
    debug("done!");
  } else {
    debug("error: can't copy file");
    exit;
  }




  // get conf file
  $conf = parse_ini_file("../config.php", true);
  if (!$conf) {
    debug("error: can't find conf file");
    exit;
  }



  // create a flickr app
  // http://www.flickr.com/services/api/auth.howto.web.html



  // step 0
  // register a flickr app, get a flickr api key & secret
  if (!$conf['flickr']['api_key'] || !$conf['flickr']['api_secret']) {
    debug("error: register a flickr app for an api key (and secret)");
    debug("visit: http://www.flickr.com/services/apps/create/");
    exit;
  }


  // step 1
  // calculate flickr frob
  // step 1: http://www.flickr.com/services/auth/?api_key=[]&perms=read&api_sig=[]
  if (!$conf['flickr']['frob']) {
    $api_sig = md5($conf['flickr']['api_secret']."api_key".$conf['flickr']['api_key']."permsread");
    $frobURL = "http://www.flickr.com/services/auth/?api_key=".$conf['flickr']['api_key']."&perms=read&api_sig=".$api_sig;
    debug("error: no frob");
    debug("visit: ".$frobURL);
    exit;
  }


  
  // step 2
  // calculate flickr auth_token
  if (!$conf['flickr']['auth_token']) {
    $api_sig = md5($conf['flickr']['api_secret']."api_key".$conf['flickr']['api_key']."formatjsonfrob".$conf['flickr']['frob']."methodflickr.auth.getTokennojsoncallback1");
    $tokenURL = "http://api.flickr.com/services/rest/?method=flickr.auth.getToken&api_key=".$conf['flickr']['api_key']."&frob=".$conf['flickr']['frob']."&format=json&nojsoncallback=1&api_sig=".$api_sig;
    $token = fetch($tokenURL);
    debug("error: no auth_token");
    debug("your auth_token: " . $token->auth->token->content);
    exit;
  }

  
  // fetch flickr sets
  debug("fetching flickr sets...");
  $api_sig = md5($conf['flickr']['api_secret']."api_key".$conf['flickr']['api_key']."auth_token".$conf['flickr']['auth_token']."formatjsonmethodflickr.photosets.getListnojsoncallback1");
  $collectionsURL = "http://api.flickr.com/services/rest/?method=flickr.photosets.getList&api_key=".$conf['flickr']['api_key']."&auth_token=".$conf['flickr']['auth_token']."&format=json&nojsoncallback=1&api_sig=".$api_sig;
  $collections = fetch($collectionsURL);
  
  $collectionCount = $collections->photosets->total;  

  if (!is_numeric($collectionCount) || $collectionCount < 0) {
    debug("error: no collections found");
    exit;
  } else {
    debug("found " . $collectionCount . " collections");    
  }



  // fetch flickr photos for each set
  foreach($collections->photosets->photoset as $photoset) {
    $title = $photoset->title->_content;
    debug("fetching photos for set: " . $title);
    $api_sig = md5($conf['flickr']['api_secret']."api_key".$conf['flickr']['api_key']."auth_token".$conf['flickr']['auth_token']."extrasdescriptionformatjsonmethodflickr.photosets.getPhotosnojsoncallback1photoset_id".$photoset->id."");
    $photosURL = "http://api.flickr.com/services/rest/?method=flickr.photosets.getPhotos&api_key=".$conf['flickr']['api_key']."&photoset_id=".$photoset->id."&extras=description&format=json&nojsoncallback=1&auth_token=".$conf['flickr']['auth_token']."&api_sig=".$api_sig;
    $photos = fetch($photosURL);
    
    foreach ($photos->photoset->photo as $photo) {
      $images[] = array(
        "title" => $photo->title,
        "description" => $photo->description->_content,
        "location" => $title,
        "src" => "http://farm".$photo->farm.".staticflickr.com/".$photo->server."/".$photo->id."_".$photo->secret."_b.jpg"
      );
    }
    debug("added " . $photos->photoset->total . " images");
  }




  // pull in page header
  $page = file_get_contents("page-chunks/header.inc.php");

  // prepare image template
  $imageTemplate = file_get_contents("page-chunks/image.inc.php");

  // loop through each image
  foreach ($images as $key => $image) {
    
    $imageData = $imageTemplate;
    
    // loop through each image value
    foreach($image as $imageKey => $imageValue) {
      $imageData = str_replace("{{".$imageKey."}}", $imageValue, $imageData);
    }

    $page .= $imageData;
  }




  // create page footer
  $footer = file_get_contents("page-chunks/footer.inc.php");

  // google analytics?
  if (isset($conf['analytics']['id'])) {
    $footer = str_replace("UA-XXX-X", $conf['analytics']['id'], $footer);
  }

  $page .= $footer;
  


  // save new index page
  if (file_put_contents("../index.html", $page)) {
    debug("New page created!");
  } else {
    debug("error: could not create page");
  }
  





// debug
function debug($message){
  echo '<p>'.$message.'</p>';
}

// flickr/curl
function fetch($url) {
  $ch = curl_init();  
  curl_setopt ($ch, CURLOPT_URL, $url);  
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);  
  curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);  
  $file_contents = curl_exec($ch);  
  curl_close($ch);
  return json_decode($file_contents);
}