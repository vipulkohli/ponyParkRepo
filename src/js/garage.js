/*BAM Software JS for PonyPark
October, 2013*/
function init() {
    function populateGarage(){


    		var parkID = document.getElementById('garageID').innerHTML;
    		console.log(parkID);
	       var request = new XMLHttpRequest();
	       //change this URL depending on what Jessica sets it from PHPApi, just pass parkID as one of the git parameters.
	       var url = 'getParkingInfo.php';
	       var data;
       

	       request.open("GET", url+'?parkingID='+parkID, true);
	       request.send();
	       request.onreadystatechange = function(e) {

	      	if(request.readyState === 4){
	                //here you'll add the necessary form elements to the form ID reportAvaForm
	                //also change the innerHTML of elemnt with ID reportAva to "Rate the Availability of " + garage_name_from_xml_http_request
	                //Do radio buttons for the availability.
	                //You'll need to make sure you have a hidden field that passes the garageID to the DB side.
	                data = request.responseText;
	                data = JSON.parse(data);
	                //data = ({"ParkingInfo":{"ParkingID":"1","Name":"Moody Garage","Address":"6004 Bishop Blvd","Cost":"2","NumberOfLevels":"4"}});
	                console.log(data);
	                document.getElementById('reportAva').innerHTML = "Rate the Availability of " + data.ParkingInfo.Name;
	                var levels = document.getElementById('level');
	                var numLevels = parseInt(data.ParkingInfo.NumberOfLevels, 10);
	                for (var i = 1; i <= numLevels; i++) {
	                	$('<option />', {
	                		value: i,
	                		text: i }).appendTo(levels);
	                }
	                
	      	}
	   	}
	        

    }


populateGarage();
}
window.onload = init;
