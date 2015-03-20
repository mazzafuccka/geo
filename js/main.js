/*global $,google */
jQuery(function($){
  var drawManager;
  var currectShape;

  function clearSelection() {
    if (currectShape) {
      currectShape.setEditable(false);
      currectShape = null;
      hidePanel();
    }
  }

  function setSelection(shape) {
    clearSelection(shape);
    currectShape = shape;
    shape.setEditable(true);
    showPanel();
    setPoints(currectShape);
  }

  function setPoints(p){
    var points = getCoord(p);
    $('#object_form').find('input[name="points"]').val(points);
  }

  function showPanel(){
    var panel = document.getElementById('panel');
    panel.style.visibility = 'visible';
  }

  function hidePanel(){
    var panel = document.getElementById('panel');
    panel.style.visibility = 'hidden';
  }

  function deleteSelectedShape() {
    if (currectShape) {
      currectShape.setMap(null);
      currectShape = null;
      drawManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
      hidePanel();
    }
  }

  function getCoord(currectShape) {
    var vertices = currectShape.getPath();
    var result = [];
    for (var i = 0; i < vertices.getLength(); i++) {
      var xy = vertices.getAt(i);
      var point = [xy.lat(), xy.lng()];
      result.push(point);
    }

    return result;
  }

  function initialize() {
    var map = new google.maps.Map(document.getElementById('map'), {
      zoom: 10,
      // default city
      center: new google.maps.LatLng(55.75222, 37.61556),
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      disableDefaultUI: true,
      zoomControl: true
    });

    var polyOptions = {
      strokeWeight: 0,
      fillOpacity: 0.45,
      editable: true
    };
    // method drawing
    // https://developers.google.com/maps/documentation/javascript/overlays?csw=1#drawing_tools
    drawManager = new google.maps.drawing.DrawingManager({
      drawingMode: google.maps.drawing.OverlayType.POLYGON,
      markerOptions: {
        draggable: true
      },
      polylineOptions: {
        editable: true
      },
      rectangleOptions: polyOptions,
      circleOptions: polyOptions,
      polygonOptions: polyOptions,
      map: map,
      drawingControlOptions: {
        // Editor panel to center
        position: google.maps.ControlPosition.TOP_CENTER,
        // types object on panel
        drawingModes: [
          //google.maps.drawing.OverlayType.CIRCLE,
          google.maps.drawing.OverlayType.POLYGON
          //google.maps.drawing.OverlayType.RECTANGLE
        ]
      }
    });

    // poligon draw complete
    google.maps.event.addListener(drawManager, 'overlaycomplete', function(e) {

      drawManager.setDrawingMode(null);
      var addShape = e.overlay;
      addShape.type = e.type;
      google.maps.event.addListener(addShape, 'click', function() {
        setSelection(addShape);
      });

      google.maps.event.addListener(drawManager, 'polygoncomplete', function(e) {
        google.maps.event.addListener(e.getPath(), 'set_at', function() {
          setSelection(addShape);
        });
        // change between point of poligon
        google.maps.event.addListener(e.getPath(), 'insert_at', function() {
          setSelection(addShape);
        });
        google.maps.event.addListener(e.getPath(), 'remove_at', function() {
          setSelection(addShape);
        });
      });

    });

    // geolocation center
    if (navigator.geolocation) {
      var showPosition = function(position) {
        map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude), 13);

      };
      navigator.geolocation.getCurrentPosition(showPosition);
    }

    // Clear the current selection when the drawing mode is changed, or when the
    // map is clicked.
    google.maps.event.addListener(drawManager, 'drawingmode_changed', clearSelection);
    // click on map
    google.maps.event.addListener(map, 'click', clearSelection);
    // delete elements, clear map
    google.maps.event.addDomListener(document.getElementById('delete-button'), 'click', deleteSelectedShape);
  }

  google.maps.event.addDomListener(window, 'load', initialize);

  var lang = null;//mapsData.lang
  //datetime picker
  $(function() {
    $('#date_timepicker_start').datetimepicker({
      format: 'd.m.Y H:i',
      lang: lang ? lang : 'en',
      onShow: function() {
        var end = $('#date_timepicker_end');
        this.setOptions({
          maxDate: end.val() ? end.val() : false
        })
      },
      timepicker: true
    });
    $('#date_timepicker_end').datetimepicker({
      format: 'd.m.Y H:i',
      lang: lang ? lang : 'en',
      onShow: function() {
        var start = $('#date_timepicker_start');
        this.setOptions({
          minDate: start.val() ? start.val() : false
        })
      },
      timepicker: true
    });
  });

  // toogle unlim
  $('input[name="unlim"]').change(function(){
    var block = $('.dateTimeWrapper-js');
    if (!this.checked){
      block.fadeIn('fast');
      $(this).val('0');
    }
    else {
      block.fadeOut('fast');
      $(this).val('1');
      // clean inputs
      $('#date_timepicker_end').val('');
      $('#date_timepicker_start').val('');
    }
  });

  // ajax save or change
  $('#save-button').click(function(){
    //todo validate form before submit
    //ajax
    var data = $('#object_form').serialize();
    $.post(ajax_object.ajax_url, data, function(response) {
      // todo check for errors
      if(response.state=='success' && !response.error){
        hidePanel();
        // clear form data point
        $('form').find('input[type="text"],textarea').val('');
      }
    }).error(function(){
      alert('Error save data on server/ try again leter/');
    });
  });
  // ajax remove

  // ajax get user object data if saved

});