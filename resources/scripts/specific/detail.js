$(function() {
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
			thousandsSep : ' '
		},
		global : {
			useUTC : false
		}
	});

	/**
	 * Synchronize Tooltips
	 */
	function syncTooltip(container, p) {
		if (p) {
			for (i = 0; i < Highcharts.charts.length; i++) {
				if (container.id !== Highcharts.charts[i].container.id) {
					Highcharts.charts[i].tooltip.refresh([
							Highcharts.charts[i].series[0].data[p],
							Highcharts.charts[i].series[1].data[p] ]);
					Highcharts.charts[i].xAxis[0].drawCrosshair(null,
							Highcharts.charts[i].series[0].data[p]);
				}
			}
		} else {
			for (i = 0; i < Highcharts.charts.length; i++) {
				if (container.id !== Highcharts.charts[i].container.id) {
					Highcharts.charts[i].tooltip.hide();
					Highcharts.charts[i].xAxis[0].hideCrosshair();
				}
			}
		}
	}

	/**
	 * Synchronize zooming through the setExtremes event handler.
	 */
	function syncExtremes(e) {
		var thisChart = this.chart;
		if (e.trigger !== 'syncExtremes') { // Prevent feedback loop
			Highcharts.each(Highcharts.charts, function(chart) {
				if (chart !== thisChart) {
					if (chart.xAxis[0].setExtremes) { // It is null while
						// updating
						chart.xAxis[0].setExtremes(e.min, e.max, undefined,
								false, {
									trigger : 'syncExtremes'
								});
					}
				}
			});
		}
	}

	$('<div class="chart">')
			.appendTo('#container')
			.highcharts(
					{
						chart : {
							zoomType : 'x',
							style : {
								fontFamily : 'Roboto'
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
						xAxis : {
							crosshair : true,
							type : 'datetime',
							tickInterval : var_interval,
							minTickInterval : var_interval * 5,
							events : {
								setExtremes : syncExtremes,
								afterSetExtremes : function(event) {

									var min = new Date(Math.ceil(event.min));
									min.setHours(0);
									min.setMinutes(0);
									min.setSeconds(0);

									var max = new Date(Math.floor(event.max));
									max.setHours(0);
									max.setMinutes(0);
									max.setSeconds(0);

									// Hidding HTML too
									$('.tablesorter > tbody > tr')
											.each(
													function() {
														var date = new Date($(
																this).data(
																'date'));
														if (((date.getTime() >= (min
																.getTime())) && (date
																.getTime() <= max
																.getTime()))
																|| (event.min === undefined)) {
															$(this).show();
														} else {
															$(this).hide();
														}
													});
								}
							}
						},
						yAxis : [ { // Primary yAxis
							title : {
								text : 'Position'
							},
							reversed : true,
							allowDecimals : false,
							opposite : true
						}, {
							title : {
								text : 'CTR'
							},
							allowDecimals : false
						} ],
						plotOptions : {
							line : {
								lineWidth : 1,
								states : {
									hover : {
										enabled : false
									}
								},
								marker : {
									enabled : false,
									states : {
										hover : {
											enabled : false
										}
									}
								},
								animation : {
									duration : 1000
								},
							},
							series : {
								point : {
									events : {
										mouseOver : function() {
											syncTooltip(
													this.series.chart.container,
													this.index);
										},
										mouseOut : function() {
											syncTooltip(this.series.chart.container);
										}
									}
								},
							}
						},
						tooltip : {
							shared : true,
							formatter : function() {
								var var_return = '';
								if (var_interval === 86400000) {
									var_return = Highcharts
											.dateFormat(
													'<span style="font-size: 10px">%A %e %B %Y</span><br/>',
													this.x);
								} else if (var_interval === 604800000) {
									var_return = Highcharts
											.dateFormat(
													'<span style="font-size: 10px">Semaine du %e %b %Y</span><br/>',
													this.x);
								}
								/** Element Value Formatting */
								$.each(this.points, function(index, element) {
									var_return += '<span style="color:'
											+ element.point.color
											+ '">\u25CF</span> '
											+ element.series.name + ': <b>'
											+ element.y + '</b><br/>';
								});
								return var_return;
							},
						},
						series : [ {
							name : 'Position',
							data : var_position,
							type : 'line',
							zoneAxis : 'x',
							zones : [ {
								value : Date.UTC(2015, 9, 5),
								color : '#66adcc'
							}, {
								color : '#4099bf',
							} ],
							min : 1,
							floor : 1,
							yAxis : 0,
							pointInterval : var_interval,
							dataGrouping : {
								enabled : false,
								smoothed : false,
								groupPixelWidth : 10
							},
							tooltip : {
								valueDecimals : 1
							}
						}, {
							name : 'CTR',
							id : 'ctr',
							data : var_ctr,
							type : 'line',
							color : '#849b24',
							zoneAxis : 'x',
							zones : [ {
								value : Date.UTC(2015, 9, 5),
								color : '#a7c42e'
							}, {
								color : '#849b24',
							} ],
							yAxis : 1,
							pointInterval : var_interval,
							tooltip : {
								valueDecimals : 1
							}
						} ]
					});
	$('<div class="chart">')
			.appendTo('#container')
			.highcharts(
					{
						chart : {
							zoomType : 'x',
							style : {
								fontFamily : 'Roboto'
							},
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
						xAxis : {
							crosshair : true,
							type : 'datetime',
							tickInterval : var_interval,
							minTickInterval : var_interval * 5,
							events : {
								setExtremes : syncExtremes
							}
						},
						yAxis : [ { // Primary yAxis
							title : {
								text : 'Impressions'
							},
							allowDecimals : false,
							opposite : true
						}, {
							title : {
								text : 'Clicks'
							},
							allowDecimals : false
						} ],
						plotOptions : {
							line : {
								lineWidth : 1,
								states : {
									hover : {
										enabled : false
									}
								},
								marker : {
									enabled : false,
									states : {
										hover : {
											enabled : false
										}
									}
								},
								animation : {
									duration : 1000
								},
							},
							series : {
								point : {
									events : {
										mouseOver : function() {
											syncTooltip(
													this.series.chart.container,
													this.index);
										},
										mouseOut : function() {
											syncTooltip(this.series.chart.container);
										}
									}
								},
							}
						},
						tooltip : {
							shared : true,
							formatter : function() {
								var var_return = '';
								if (var_interval === 86400000) {
									var_return = Highcharts
											.dateFormat(
													'<span style="font-size: 10px">%A %e %B %Y</span><br/>',
													this.x);
								} else if (var_interval === 604800000) {
									var_return = Highcharts
											.dateFormat(
													'<span style="font-size: 10px">Semaine du %e %b %Y</span><br/>',
													this.x);
								}
								/** Element Value Formatting */
								$.each(this.points, function(index, element) {
									var_return += '<span style="color:'
											+ element.point.color
											+ '">\u25CF</span> '
											+ element.series.name + ': <b>'
											+ element.y + '</b><br/>';
								});
								return var_return;
							},
						},
						series : [ {
							name : 'Impressions',
							data : var_impression,
							type : 'line',
							zoneAxis : 'x',
							zones : [ {
								value : Date.UTC(2015, 9, 5),
								color : '#66adcc'
							}, {
								color : '#4099bf',
							} ],
							yAxis : 0,
							pointInterval : var_interval,
							tooltip : {
								valueDecimals : 0
							}
						}, {
							name : 'Clicks',
							id : 'ctr',
							data : var_click,
							type : 'line',
							color : '#849b24',
							zoneAxis : 'x',
							zones : [ {
								value : Date.UTC(2015, 9, 5),
								color : '#a7c42e'
							}, {
								color : '#849b24',
							} ],
							yAxis : 1,
							pointInterval : var_interval,
							tooltip : {
								valueDecimals : 0
							}
						} ]
					});
});

$(function() {
	// Table Sort
	$('#list').tablesorter();

	// Click on Table Event
	$(document).on(
			'mousedown',
			'.clickable',
			function(event) {
				// Middle Click or CTRL Click
				if ((event.which === 2) || ((event.which === 1) && (event.ctrlKey === true))) {
					window.open($(this).data('href'), 'window name');
				} else {
					// Normal Click
					window.document.location = $(this).data('href');
				}
				event.preventDefault();
				return false;
			});
});