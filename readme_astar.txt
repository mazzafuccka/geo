
  astar_json.php 

  php implementation of pathfinding algorithm

  call by .ajax() with jQuery. pass arguments by POST or GET method:

  start_x (lng of start point)
  start_y (lat of start point)

  end_x (lng of destination point)
  end_y (lat of destination point)

  return javascript array of coordinates [[lat, lng]]:

var st_pnt=[15 ,24];var end_pnt=[31 ,15];var path = [[41.5013781117, 1.57928466797],[41.5083575841, 1.57928466797], [41.4883575841, 1.59928466797], [41.4683575841, 1.61928466797], [41.4483575841, 1.63928466797], [41.4283575841, 1.65928466797], [41.4083575841, 1.67928466797], [41.3883575841, 1.69928466797], [41.3683575841, 1.71928466797], [41.3483575841, 1.73928466797], [41.3283575841, 1.75928466797], [41.3283575841, 1.77928466797], [41.3283575841, 1.79928466797], [41.3283575841, 1.81928466797], [41.3283575841, 1.83928466797], [41.3283575841, 1.85928466797], [41.3283575841, 1.87928466797], [41.3283575841, 1.89928466797], [41.3283575841, 1.89239501953] ]; alert('loaded ok, pregrad 65');

  to load this data into array, use .eval() or .globalEval() functions. example of usage:

  		$.ajax({
      			type: "GET",
      			url: "astar_json2.php?start_x="+x1+"&start_y="+y1+"&end_x="+x2+"&end_y="+y2, 
			cache: false,
            		context: document.body,
            		success: function(responseText) {
                		$(".site-info").text(responseText);
				draw_poly();
			}
    		});
    		
		function draw_poly()
		{

		jQuery.globalEval( $(".site-info").text() );
		var myline = [];
		
		for (var i=0; i<path.length; i++)
		{
			var lng = path[i][1];	
			var lat = path[i][0];
			myline.push(new google.maps.LatLng(lat, lng));
		}

		var flightPath = new google.maps.Polyline({
 			path: myline,
    			geodesic: false,
    			strokeColor: '#FF0000',
    			strokeOpacity: 1.0,
    			strokeWeight: 2
  		});
				
  		flightPath.setMap(map);		

