<?php
function getData(){
 // Connect to MySQL (db Hostname/IP, user, password, database)
	$link = new mysqli( '192.168.10.10', 'user', 'password', 'mymqttdb' );
	if ( $link->connect_errno ) {
		die( "Failed to connect to MySQL: (" . $link->connect_errno . ") " . $link->connect_error );
	}	
	$start = $_GET['startDate'];		// Get parameter from URL
	$ende = $_GET['endDate'];			// Get parameter from URL

	if (!isset($endDate )){				// No end date? then actual for end
		$endDate = date('Y-m-d H:i', time());
	}	
	if (!isset($startDate )){			// No start date? then actual -1 day for start
		$startDate = date('Y-m-d H:i', strtotime('-1 day', strtotime($ende)));
	}
	// Query in SQL ! add your own columns and database table name!
	$query= "SELECT `DateTime`,`temperature`,`humidity` FROM `Chickenhouse` WHERE `DateTime` BETWEEN" . "'" . $startDate ."'" . "AND" . "'" . $endDate ."'";
	$result = $link->query($query);		// make db query
	
	$rows = array();
	$table = array(); 
		
	$table['cols'] = array
	(
	array('label' => 'Date Time', 'type' => 'datetime'),
	array('label' => 'Temperatur (°C)', 'type' => 'number'),		// Select your label for the index
	array('label' => 'Luftfeuchtigkeit (%)', 'type' => 'number')	// Select your label for the index
	); 
  
	while($row = mysqli_fetch_array($result))		// got to all the lines of the query result
	{
		$sub_array = array();
		$date1 = new DateTime($row['DateTime']);
		$date2 = "Date(".date_format($date1, 'Y').", ".((int) date_format($date1, 'm') - 1).", ".date_format($date1, 'd').", ".date_format($date1, 'H').", ".date_format($date1, 'i').", ".date_format($date1, 's').")";
		$sub_array[] =  array("v" => (string)$date2);
		$sub_array[] =  array("v" => $row["temperature"]);
		$sub_array[] =  array("v" => $row["humidity"]);
		$rows[] =  array("c" => $sub_array);
	}
	$table['rows'] = $rows;
	$lineCount = count($rows);							// Number of array fields (lines) to show in browser
	return array(json_encode($table), $lineCount);		// Make JSON from array and give it to the java script together with linecount
}
?> 
 
<html>
	<head>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.2.3/flatpickr.css">
	<style>
	*{font-family:Arial;}
		.page-wrapper{ width:90%; margin:0 auto; }
		input { border: 2px solid whitesmoke;border-radius: 12px; padding: 12px 10px; text-align: center;  font-size: 16px; font-weight: bold; width: 250px;background: cornflowerblue; color: yellow;}
		button { border: none; border-radius: 10px; text-align: center; padding: 12px 10px; cursor: pointer; font-weight: bold; background: cornflowerblue; color: white;}
	</style>
	</head>
	<body>
		<div class="page-wrapper">	</div>	
		<input type="text" style="float:left" id="rangeDate" placeholder="Select Timespan" data-input>
		<br>
		<p id="LineCount" > </p>
		<div id="line_chart" style="width: 100%; height: 800px"></div>
	  	
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.2.3/flatpickr.js"></script>
		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
		<script>

// Flatpickr to select date range
$("#rangeDate").flatpickr({
	enableTime: false,
	mode: 'range',
	time_24hr: true,
	dateFormat: "Y-m-d",
	maxDate: "today",
	defaulDate: "today",
	onClose: function test(selectedDates, dateStr, instance){
		arDateTime = dateStr.split(" to ");
		dateTimeStart = arDateTime[0] + " 00:00" ;
		dateTimeEnd =  arDateTime[1] + " 23:59" ;
		strNeu = "?startDate=" + dateTimeStart + "&endDate=" + dateTimeEnd;
		window.location = strNeu;
	},
});

// Setup and show Google line chart
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);
function drawChart(){
	var data = new google.visualization.DataTable(<?php echo getData()[0]?>);		//  Call PHP-Function an receive JSON
	document.getElementById("LineCount").innerHTML= "  " + <?php echo getData()[1]?> + " Records loaded";	// Get record count
	var options = {
		series: {
			0:{color: 'red', visibleInLegend: true, targetAxisIndex: 0},
			1:{color: 'blue', visibleInLegend: true, targetAxisIndex: 1}
		},
		vAxes: {
			// Adds labels to each axis; they don't have to match the axis names.
			0: {title: 'Temp (°C)' }, // , 'minValue': 0, 'maxValue': 30
			1: {title: 'Feuchte(%)'}
		},
		title:'Chickenhouse',
		legend:{position:'top'},
		chartArea:{width:'75%', height:'65%'},
		//curveType: 'function',
		hAxis: {
			title: 'Datum',  titleTextStyle: {color: '#333'},
			format: 'd.M HH:mm',
			slantedText:true, slantedTextAngle:80
		},
		explorer: { 
			actions: ['dragToPan', 'dragToZoom', 'rightClickToReset'],	// 'dragToZoom' 
			axis: 'horizontal',
			keepInBounds: true,
			maxZoomIn: 28.0,
			maxZoomOut: 1.0,
			zoomDelta: 1.5
		},
		colors: ['#D44E41'],
	};
	var date_formatter = new google.visualization.DateFormat({ // Tooltip format
    pattern: "dd.MM.yyyy -   HH:mm"
	}); 
	date_formatter.format(data, 0);
	var chart = new google.visualization.LineChart(document.getElementById('line_chart'));
	chart.draw(data, options);

	
	// Select / deselect lines by clicking on the label
	    var columns = [];
    var series = {};
    for (var i = 0; i < data.getNumberOfColumns(); i++) {
        columns.push(i);
        if (i > 0) {
            series[i - 1] = {};
        }
    }
	google.visualization.events.addListener(chart, 'select', function () {
        var sel = chart.getSelection();
        // if selection length is 0, we deselected an element
        if (sel.length > 0) {
            // if row is undefined, we clicked on the legend
            if (sel[0].row === null) {
                var col = sel[0].column;
                if (columns[col] == col) {
                    // hide the data series
                    columns[col] = {
                        label: data.getColumnLabel(col),
                        type: data.getColumnType(col),
                        calc: function () {
                            return null;
                        }
                    };
                    
                    // grey out the legend entry
                    series[col - 1].color = '#CCCCCC';
                } else {
                    // show the data series
                    columns[col] = col;
                    series[col - 1].color = null;
                }
                var view = new google.visualization.DataView(data);
                view.setColumns(columns);
                chart.draw(view, options);
            }
        }
    });
};
		</script>
	</body>
</html>