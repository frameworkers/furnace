<?php
namespace org\frameworkers\furnace\utilities;

class HTTP {
	
	const HTTP_400 = "400 Bad Request";
	const HTTP_403 = "403 Forbidden";
	const HTTP_404 = "404 Not Found";
	const HTTP_405 = "405 Method Not Allowed";
	const HTTP_412 = "412 Precondition Failed";
	
	const HTTP_BAD_REQUEST = self::HTTP_400;
	const HTTP_FORBIDDEN = self::HTTP_403;
	const HTTP_NOT_FOUND = self::HTTP_404;
	const HTTP_METHOD_NOT_ALLOWED  = self::HTTP_405;
	const HTTP_PRECONDITION_FAILED = self::HTTP_412;
}