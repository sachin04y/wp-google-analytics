var GNLY = {
	
	init: () => {

		google.load('visualization', '1', { packages: ['controls'], callback: GNLY.drawTableChart });

		var container = document.querySelector('#gnly-analytics-report-area');

		if (undefined != container && null != container) {

			GNLY.reportFilters = container.querySelectorAll('.gnly-report-filter');
			GNLY.loader = container.querySelector('.gnly-loader');

			GNLY.reportFilters.forEach((filter) => {

				GNLY[filter.id] = filter.value;

				filter.addEventListener('change', () => {
					GNLY[filter.id] = filter.value;
					GNLY.fetchReport();
				});
			});

			GNLY.fetchReport();
		}
	},
	fetchReport: () => {

		GNLY.loader.setAttribute('state', 'on');

		var params = {
			action: 'gnly_analytics_report',
			security: gnlyObj.security,
			projectId: GNLY.profileId,
			from: GNLY.period,
			to: 'yesterday'
		};

		jQuery.post(gnlyObj.ajaxurl, params, function (report) {
			GNLY.drawTableChart(report)
		})
			.done(() => {
				GNLY.loader.setAttribute('state', 'off');
			})
			.fail(() => {
				alert('Request failure.');
			});
	},
	drawTableChart: (report) => {

		// Set chart options
		var options = {
			showRowNumber : true,
			width: '100%',
			height: '600',
			pageSize : document.querySelector('#gnly-per-page').value,
		};
		
		data = google.visualization.arrayToDataTable(report);

		var dashboard = new google.visualization.Dashboard(document.querySelector('#gnly-dashboard'));

		var stringFilter = new google.visualization.ControlWrapper({
			controlType: 'StringFilter',
			containerId: 'gnly-report-search-filter',
			options: {
				filterColumnIndex: 0,
				matchType: 'any'
			}
		});

		var catFilter = new google.visualization.ControlWrapper({
			controlType: 'StringFilter',
			containerId: 'gnly-report-cat-filter',
			options: {
				filterColumnIndex: 0,
				matchType: 'any',
			}
		});

		
		var table = new google.visualization.ChartWrapper({
			chartType: 'Table',
			containerId: 'view-selector-container',
			options: options
		});
		
		dashboard.bind([stringFilter, catFilter], [table]);
		dashboard.draw(data);

		var spoofCatFilter = document.querySelector('#gnly-cat-filter-spoof');
		spoofCatFilter.addEventListener('change', function () {
			var mainCatFilter = document.querySelector('#gnly-report-cat-filter input');
			mainCatFilter.value = this.value;
			mainCatFilter.dispatchEvent(new Event('keyup'));
		});

		// // Create a dashboard.
		// // var dashboard = new google.visualization.Dashboard(document.getElementById('gnly-dashboard'));
		
		// var chart = new google.visualization.Table(document.getElementById('view-selector-container'));

		// // var chart = new google.visualization.ChartWrapper({
		// // 	chartType: 'Table',
		// // 	containerId: 'view-selector-container',
		// // 	options: options,
		// // });


		// // var stringFilter = new google.visualization.ControlWrapper({
		// // 	controlType: 'StringFilter',
		// // 	containerId: 'gnly-report-search-filter',
		// // 	options: {
		// // 		filterColumnIndex: 0
		// // 	}
		// // });

		// // dashboard.bind(stringFilter, chart);
		// // dashboard.draw();

		// google.visualization.events.addListener(chart, 'ready', () => {
		// 	jQuery('.google-visualization-table-table').filtable({ controlPanel: jQuery('.table-filters') });
		// });

		// chart.draw(data, options);

	}
};

document.addEventListener('DOMContentLoaded', () => { GNLY.init() });
