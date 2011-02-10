<?php
use org\frameworkers\furnace\response\ResponseTypes;

// Standard HTML output
ResponseTypes::Register('html', 'text/html',
	array(
		"class"  => "org\\frameworkers\\furnace\\response\\HtmlResponse",
		"engine" => "org\\frameworkers\\furnace\\response\\renderers\\TadpoleRenderer"
	)
);

// JSON output
ResponseTypes::Register('json', 'application/json', 
	array(
		"class"  => "org\\frameworkers\\furnace\\response\\JsonResponse",
		"engine" => "org\\frameworkers\\furnace\\response\\renderers\\JsonRenderer"
	)
);
	