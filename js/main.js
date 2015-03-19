/*global google */
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
  console.log(result);

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
        google.maps.drawing.OverlayType.CIRCLE,
        google.maps.drawing.OverlayType.POLYGON,
        google.maps.drawing.OverlayType.RECTANGLE
      ]
    }
  });

  google.maps.event.addListener(drawManager, 'overlaycomplete', function(e) {
    if (e.type != google.maps.drawing.OverlayType.MARKER) {
      drawManager.setDrawingMode(null);
      var addShape = e.overlay;
      addShape.type = e.type;
      google.maps.event.addListener(addShape, 'click', function() {
        setSelection(addShape);
      });
      setSelection(addShape);
    }
  });

  // Clear the current selection when the drawing mode is changed, or when the
  // map is clicked.
  google.maps.event.addListener(drawManager, 'drawingmode_changed', clearSelection);
  // click on map
  google.maps.event.addListener(map, 'click', clearSelection);
  // delete elements, clear map
  google.maps.event.addDomListener(document.getElementById('delete-button'), 'click', deleteSelectedShape);
}
google.maps.event.addDomListener(window, 'load', initialize);