<?php
/**
 * Template Name: Search
 **/

function spanOrder($sort, $order, $thisSpan) {
  if ($sort.$order==$thisSpan) {
    return '<span/>';
  }
  return '';
}


?>

<?php get_header(); ?>
<div id="content">

<div class="solr clearfix">

  <?php $results = mss_search_results(); ?>  

<div class="solr1 clearfix">
          <div class="solr_search">

            <form name="searchbox" method="get" id="searchbox" action="">
          <input id="qrybox" name="s" type="text" class="solr_field" value="<?php echo $results['query'] ?>"/><input id="searchbtn" type="submit" value="Search" class="submit button" style="width:100px; height:30px;" />
            </form>




<?php if($results['dym']) {
  printf("<div class='solr_suggest'>Did you mean: <a href='%s'>%s</a> ?</div>", $results['dym']['link'], $results['dym']['term']);
} ?>

  </div>

  <div class="solr2_nofacets">
    <div class="solr_results_header clearfix">
      <div class="solr_results_header">

<?php if ($results['hits'] && $results['query'] && $results['qtime']) {
  if ($results['firstresult'] === $results['lastresult']) {
    printf("Displaying result %s of <span id='resultcnt'>%s</span> hits", $results['firstresult'], $results['hits']);
  } else {
    printf("Displaying results %s-%s of <span id='resultcnt'>%s</span> hits", $results['firstresult'], $results['lastresult'], $results['hits']);
  }
?>
  </div>

<?php 
  $sort = (isset($_GET['sort'])) ? $_GET['sort'] : 'score';
  $order = (isset($_GET['order'])) ? $_GET['order'] : 'desc';
?>

    <div class="solr_results">

<?php if ($results['hits'] === "0") {
  printf("<div class='solr_noresult'>
    <h2>Sorry, no results were found.</h2>
    <h3>Perhaps you mispelled your search query, or need to try using broader search terms.</h3>
    <p>For example, instead of searching for 'London Hotels', try something simple like 'London'.</p>
    </div>\n");
} elseif ($results['hits'] == "1") {
  print "<meta http-equiv=\"refresh\" content=\"0;URL='".$results['results'][0]['permalink']."'\" />";  
  print "<h3>loading it ... </h3>";
} else {
  $tabs = Array();
  $unsorted_tabs = $results['results'];

  if (is_array($unsorted_tabs)) foreach($unsorted_tabs as $result) {
    $weight_str = (isset($result['weight_str'])) ? $result['weight_str'] : '10';
    $weight = intval($weight_str);
    $result['weight'] = $weight;
    $unsorted_tabs_weight[$weight][] = $result; 
  }

  ksort($unsorted_tabs_weight);

  foreach($unsorted_tabs_weight as $unsorted_tabs) {
    foreach($unsorted_tabs as $result) {
      if ( strrpos($result['permalink'],"/hotel/" ) ) { 
        $tabs['Hotel'][] = $result ;
      } elseif ( strrpos($result['permalink'],"/destinations/") ) {
        $tabs['Destinations'][] = $result ;
      } elseif ( $result['permalink'] ) {
        $tabs['Else'][] = $result ;
      };
    };
  };
  if (isset($tabs['Hotel'][0])) {
    if (count($tabs) > 1) {
      if (isset($tabs['Hotel'][1])) {
        $output[] = sprintf("<h2>Hotels</h2>");
      } else {
        $output[] = sprintf("<h2>Hotel</h2>");
      }
    }

    ///* facets

    if ($results['facets'] && $results['hits'] != 1) {
      $output[] = sprintf(' <div class="solr3"><ul class="solr_facets"> ');
      foreach ($results['facets']['selected'] as $selected ) {
        $arr_selected[] = str_replace('Facilities:&nbsp;"', '', $selected['name']);
      }

      foreach($results['facets'] as $facet) {
        // if ( sizeof($facet["items"]) > 1  && !in_array($facet['name'].'"', $arr_selected) ) { #don't display facets with only 1 value or selected
        if (isset($facet['name'])) {
          $taxonomies=get_taxonomies(array('name' =>  strtolower ($facet['name'] )),'objects');
          if  ($taxonomies) {
            foreach ($taxonomies as $taxonomy ) {
              $facet['name'] = $taxonomy->label;
            }
          }
          $output[] = sprintf("<li><h3>%s</h3>\n", $facet['name']);
          foreach ($facet["items"] as $item) {
            if ( $results['hits'] > $item["count"] ) $output[] = "<li><a href=\"" . $item["link"]  . "\"> " . $item["name"] . " (". $item["count"] .") </a> </li>";
          };
          $output[] = sprintf("</li>\n");
        }
        // }
      }
      $output[] = sprintf("</ul></div>");
    }
    //*/ // end facets

    @$min_price = unserialize(file_get_contents("/home/customers/travelguide/www.essentialworld.travel/www/wp-content/plugins/advanced-search-by-my-solr-server/template/cache_file"));   
    if (!is_array($min_price)) {$min_price = array();}

    $star = "<span class='star' >*</span>";
    $output[] = "<ul class='result' id='archivecss' >";
    foreach($tabs['Hotel'] as $result) {
      if ( isset( $_GET['debug'])) {
        print print_r($result ,1); 
      }
      if (is_array($result['tags'] )) { 
        foreach ($result['tags'] as $tag ) {
          if ($tag == "1 Star") $stars = $star;
          if ($tag == "2 Star") $stars = $star. $star;
          if ($tag == "3 Star") $stars = $star. $star. $star;
          if ($tag == "4 Star") $stars = $star. $star. $star. $star;
          if ($tag == "5 Star") $stars = $star. $star. $star. $star. $star;
        }
      }
      if ( $result['bookingservices_str'] == 1 ) {// venere
        $book = "<iframe class=\"card\" src='" . $result['permalink'] . '?iframe=1\' ></iframe>';


        // if ( isset( $_GET['debug'])) {print print_r($min_price ,1); }
        if ( (int) $result['hotelid_str'] > 0)  {
          if (!isset($min_price[ $result['hotelid_str'] ] )) {
            $min_price[ $result['hotelid_str'] ] = file_get_contents("https://essentialhotels.co.uk/venere/lib/find_room_names.php?price=1&id=".$result['hotelid_str']."&ota=".$result['bookingservices_str']);
          }
          if ($min_price[ (int) $result['hotelid_str'] ] > 1) {
            $prices_from = "From £" . $min_price[ $result['hotelid_str'] ];
          } else {
            $prices_from = "Phone only deal";
          }
        }
      } else {
        $book = "Phone only deal 0118 971 4700";

      }
      if (is_array($result['searchimage_str']) ) {
        $result['searchimage_str'] = $result['searchimage_str'][0];
      }
      if ($result['searchimage_str'] == '') $result['searchimage_str'] = "essentialhotels.co.uk/wp-content/uploads/2014/06/no-image-available-use.jpg";
      if ($result['searchimage_str'] != '') {

        $output[] = sprintf("<li class='post'>", $result['permalink']);
        $output[] = '[tabs]'; 
        $output[] = '  [tab title="Info"]'; 
        $output[] =      sprintf("<div class=\"solr_image\"><a href='%s'><img src='//sdyrcs.cloudimage.io/s/resize/245/%s' title='%s' /></a></div>\n\n<h2><a href='%s'>%s</a></h2>\n", $result['permalink'], $result['searchimage_str'], $result['title'], $result['permalink'], $result['title'] );
        // src=http://st5lte.cloudimage.io/s/resize/296/' . $_image_url . '&key=image&width=296&height=174&link=img
        $output[] = "    <div class='stars'> $stars </div>";
        if (isset($prices_from) ) {
          $output[] = "  <div class='price-from'>$prices_from </div>";
        }
        $output[] = '  [/tab]';

        $output[] = ' [tab title="Book"] '; 
        $output[] =     $book; 
        $output[] = ' [/tab] '; 

        $output[] = '[/tabs] </li>'; 

      }

    };
    //if (rand()&100 == 100) $min_price = array();
    file_put_contents("/home/customers/travelguide/www.essentialworld.travel/www/wp-content/plugins/advanced-search-by-my-solr-server/template/cache_file", serialize($min_price ));
    //}
    $output[] = sprintf("</ul>\n");
    // <--- end hotels 
  };
  if (count($tabs['Destinations']) > 1) {
    $output[] = sprintf("<h2>Destinations</h2>");
    $output[] = "<ul class='result' id='archivecss' >";
    foreach($tabs['Destinations'] as $result) {
      if ( isset( $_GET['debug'])) {
        print "Destinations" . print_r($result ,1); 
      }
      if (is_array($result['searchimage_str']) ) {
        $result['searchimage_str'] = $result['searchimage_str'][0];
      }
      if ($result['searchimage_str'] == '') $result['searchimage_str'] = "essentialhotels.co.uk/wp-content/uploads/2014/06/no-image-available-use.jpg";
      if ($result['searchimage_str'] != '') {

        $output[] = sprintf("<li class='post'>", $result['permalink']);
        $output[] = '[tabs]'; 
        $output[] = '  [tab title="Info"]'; 
        $output[] =      sprintf("<div class=\"solr_image\"><a href='%s'><img src='//sdyrcs.cloudimage.io/s/resize/245/%s' title='%s' /></a></div>\n\n<h2><a href='%s'>%s</a></h2>\n", $result['permalink'], $result['searchimage_str'], $result['title'], $result['permalink'], $result['title'] );
        // src=http://st5lte.cloudimage.io/s/resize/296/' . $_image_url . '&key=image&width=296&height=174&link=img
        $output[] = '  [/tab]';

        // $output[] = '  [tab title="Map"]'; 
        // $output[] =      $map; 
        // $output[] = '   [/tab] '; 

        $output[] = '[/tabs] </li>'; 

      };
    };
    $output[] = "</ul>";
  };
  if (count($tabs['Else']) > 1) {
    $output[] = sprintf("<h2>Attractions</h2>");
    $output[] = "<ul class='result' id='archivecss' >";
    foreach($tabs['Else'] as $result) {
      if ( isset( $_GET['debug'])) {
        print "Destinations" . print_r($result ,1); 
      }
      if (is_array($result['searchimage_str']) ) {
        $result['searchimage_str'] = $result['searchimage_str'][0];
      }
      if ($result['searchimage_str'] == '') $result['searchimage_str'] = "essentialhotels.co.uk/wp-content/uploads/2014/06/no-image-available-use.jpg";

      if ($result['searchimage_str'] == '') $result['searchimage_str'] = "essentialhotels.co.uk/wp-content/uploads/2014/06/no-image-available-use.jpg";
      if ($result['searchimage_str'] != '') {

        $output[] = sprintf("<li class='post'>", $result['permalink']);
        $output[] = '[tabs]'; 
        $output[] = '  [tab title="Info"]'; 
        $output[] =      sprintf("<div class=\"solr_image\"><a href='%s'><img src='//sdyrcs.cloudimage.io/s/resize/245/%s' title='%s' /></a></div>\n\n<h2><a href='%s'>%s</a></h2>\n", $result['permalink'], $result['searchimage_str'], $result['title'], $result['permalink'], $result['title'] );
        $output[] = '  [/tab]';

        //$output[] = '  [tab title="Map"]'; 
        //$output[] =      $Map; 
        //$output[] = '   [/tab] '; 

        $output[] = '[/tabs] </li>'; 
      }
    }

    $output[] = "</ul>";
  }
  print do_shortcode(hhost_add_links(join($output) ));
 }
}
?>


    </div>  
  </div>
</div>

</div>
<?php get_footer(); ?>
