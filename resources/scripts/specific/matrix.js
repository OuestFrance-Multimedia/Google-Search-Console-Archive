$(function() {
	// Table Sort
	$('#list').tablesorter();

	$.fn.graphup.colorMaps.colorMap = [ [ 221, 68, 55 ], [ 232, 129, 120 ], [],
	                        			[ 180, 209, 58 ], [ 132, 155, 36 ] ];
	                        	$.fn.graphup.colorMaps.colorMapInverted = [ [ 132, 155, 36 ],
	                        			[ 180, 209, 58 ], [], [ 232, 129, 120 ], [ 221, 68, 55 ] ];
	                        	$('#list td._i').graphup({
	                        		colorMap : 'colorMap',
	                        		painter : 'fill',
	                        		max : 20,
	                        		min : -15
	                        	});
	                        	$('#list td._c').graphup({
	                        		colorMap : 'colorMap',
	                        		painter : 'fill',
	                        		max : 3,
	                        		min : -3
	                        	});
	                        	$('#list td._p').graphup({
	                        		colorMap : 'colorMapInverted',
	                        		painter : 'fill',
	                        		max : 1,
	                        		min : -1
	                        	});
	                        	
	                        	
	Highcharts.setOptions({
		lang : {
			months : [ 'janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin',
					'juillet', 'août', 'septembre', 'octobre', 'novembre',
					'decembre' ],
			weekdays : [ 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi',
					'Vendredi', 'Samedi' ],
			shortMonths : [ 'Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil',
					'Aout', 'Sept', 'Oct', 'Nov', 'Dec' ],
			decimalPoint : ',',
			downloadPNG : 'Telecharger en image PNG',
			downloadJPEG : 'Telecharger en image JPEG',
			downloadPDF : 'Telecharger en document PDF',
			downloadSVG : 'Telecharger en document Vectoriel',
			exportButtonTitle : 'Export du graphique',
			loading : 'Chargement en cours...',
			printButtonTitle : 'Imprimer le graphique',
			resetZoom : 'Reinitialiser le zoom',
			resetZoomTitle : 'Reinitialiser le zoom au niveau 1:1',
			thousandsSep : ' ',
			decimalPoint : ','
		},
		global : {
			useUTC : false
		}
	});
	
	$('<div class="full-chart">')
			.appendTo('#container')
			.highcharts(
					{
						chart : {
				            type: 'bubble',
				            plotBorderWidth: 1,
				            zoomType: 'xy',
							style : {
								fontFamily : 'Roboto',
								margin: [0, 0, 0, 0],
								spacing: [0, 0, 0, 0]
							}
						},
						title : {
							text : null
						},
						credits : {
							enabled : false
						},
						legend : {
							enabled : true
						},
						rangeSelector : {
							enabled : false
						},
						xAxis: {
							min : -100,
							max : 100,
							title: {
				                text: var_xAxis + ((var_xAxis != 'Position') ? ' %' : '')
				            },
				            tickAmount: 5,
				            showFirstLabel: false,
				            showLastLabel: false,
				            plotLines: [{
				                color: '#777',
				                width: 1,
				                value: 0,
				                zIndex: 3
				            }]
						},
						yAxis: {
							min : -100,
							max : 100,
							title: {
				                text: var_yAxis + ((var_yAxis != 'Position') ? ' %' : ''),
				            },
				            tickAmount: 5,
				            showFirstLabel: false,
				            showLastLabel: false,
				            plotLines: [{
				                color: '#777',
				                width: 1,
				                value: 0,
				                zIndex: 3
				            }]
						},
						plotOptions : {
							series: {
								dataLabels: {
				                    enabled: true,
				                    format: '{series.name}',
				                    y: 10,
				                    inside: true,
				                    style: {
				                        fontWeight: 'plain',
				                        textShadow: 'none',
				                        color: 'black'
				                    },
				                    allowOverlap: false
								}
							},
							bubble: {
								sizeBy: 'size'
							}
						},
				        tooltip: {
				            useHTML: true,
				            headerFormat: '',
				            pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}<br/>' +
				            			 var_xAxis + ' : <b>{point.x} ' + ((var_xAxis != 'Position') ? ' %' : '') + '</b><br/>' +
				            			 var_yAxis + ' : <b>{point.y} ' + ((var_yAxis != 'Position') ? ' %' : '') + '</b><br/>' +
				            			 var_zAxis + ' (Volume) : <b>{point.z}</b><br/>',
				            	
				        },
						series: var_data
					});	
});