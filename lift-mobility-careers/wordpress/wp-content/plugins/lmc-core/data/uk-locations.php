<?php
/** UK regions/nations and their counties / principal areas, used for structured location search. */
defined( 'ABSPATH' ) || exit;

function lmc_uk_locations() {
	return array(
		'London'                   => array( 'Greater London' ),
		'South East'               => array( 'Kent', 'Surrey', 'East Sussex', 'West Sussex', 'Hampshire', 'Isle of Wight', 'Berkshire', 'Buckinghamshire', 'Oxfordshire' ),
		'South West'               => array( 'Bristol', 'Cornwall', 'Devon', 'Dorset', 'Gloucestershire', 'Somerset', 'Wiltshire' ),
		'East of England'          => array( 'Bedfordshire', 'Cambridgeshire', 'Essex', 'Hertfordshire', 'Norfolk', 'Suffolk' ),
		'East Midlands'            => array( 'Derbyshire', 'Leicestershire', 'Lincolnshire', 'Northamptonshire', 'Nottinghamshire', 'Rutland' ),
		'West Midlands'            => array( 'Herefordshire', 'Shropshire', 'Staffordshire', 'Warwickshire', 'West Midlands County', 'Worcestershire' ),
		'Yorkshire and the Humber' => array( 'East Riding of Yorkshire', 'North Yorkshire', 'South Yorkshire', 'West Yorkshire' ),
		'North West'               => array( 'Cheshire', 'Cumbria', 'Greater Manchester', 'Lancashire', 'Merseyside' ),
		'North East'               => array( 'County Durham', 'Northumberland', 'Tyne and Wear', 'Teesside' ),
		'Wales'                    => array( 'North Wales', 'Mid Wales', 'South West Wales', 'South East Wales', 'Cardiff' ),
		'Scotland'                 => array( 'Glasgow & Strathclyde', 'Edinburgh & Lothians', 'Aberdeen & Grampian', 'Highlands & Islands', 'Tayside & Fife', 'Central Scotland', 'Scottish Borders', 'Dumfries & Galloway' ),
		'Northern Ireland'         => array( 'Belfast', 'County Antrim', 'County Armagh', 'County Down', 'County Fermanagh', 'County Londonderry', 'County Tyrone' ),
	);
}
