<?php

namespace Mapbender\SearchBundle\Utils;

/**
 * Class QueryManagerTest
 *
 * @package Mapbender\DataSourceBundle\Utils
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class HTTPStatusConstants
{

    const _CONTINUE                                                  = 100;
    const _SWITCHING_PROTOCOLS                                       = 101;
    const _PROCESSING                                                = 102;  // RFC2518
    const _OK                                                        = 200;
    const _CREATED                                                   = 201;
    const _ACCEPTED                                                  = 202;
    const _NON_AUTHORITATIVE_INFORMATION                             = 203;
    const _NO_CONTENT                                                = 204;
    const _RESET_CONTENT                                             = 205;
    const _PARTIAL_CONTENT                                           = 206;
    const _MULTI_STATUS                                              = 207;  // RFC4918
    const _ALREADY_REPORTED                                          = 208;  // RFC5842
    const _IM_USED                                                   = 226;  // RFC3229
    const _MULTIPLE_CHOICES                                          = 300;
    const _MOVED_PERMANENTLY                                         = 301;
    const _FOUND                                                     = 302;
    const _SEE_OTHER                                                 = 303;
    const _NOT_MODIFIED                                              = 304;
    const _USE_PROXY                                                 = 305;
    const _RESERVED                                                  = 306;
    const _TEMPORARY_REDIRECT                                        = 307;
    const _PERMANENTLY_REDIRECT                                      = 308;  // RFC7238
    const _BAD_REQUEST                                               = 400;
    const _UNAUTHORIZED                                              = 401;
    const _PAYMENT_REQUIRED                                          = 402;
    const _FORBIDDEN                                                 = 403;
    const _NOT_FOUND                                                 = 404;
    const _METHOD_NOT_ALLOWED                                        = 405;
    const _NOT_ACCEPTABLE                                            = 406;
    const _PROXY_AUTHENTICATION_REQUIRED                             = 407;
    const _REQUEST_TIMEOUT                                           = 408;
    const _CONFLICT                                                  = 409;
    const _GONE                                                      = 410;
    const _LENGTH_REQUIRED                                           = 411;
    const _PRECONDITION_FAILED                                       = 412;
    const _REQUEST_ENTITY_TOO_LARGE                                  = 413;
    const _REQUEST_URI_TOO_LONG                                      = 414;
    const _UNSUPPORTED_MEDIA_TYPE                                    = 415;
    const _REQUESTED_RANGE_NOT_SATISFIABLE                           = 416;
    const _EXPECTATION_FAILED                                        = 417;
    const _I_AM_A_TEAPOT                                             = 418;   // RFC2324
    const _MISDIRECTED_REQUEST                                       = 421;   // RFC7540
    const _UNPROCESSABLE_ENTITY                                      = 422;   // RFC4918
    const _LOCKED                                                    = 423;   // RFC4918
    const _FAILED_DEPENDENCY                                         = 424;   // RFC4918
    const _RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
    const _UPGRADE_REQUIRED                                          = 426;   // RFC2817
    const _PRECONDITION_REQUIRED                                     = 428;   // RFC6585
    const _TOO_MANY_REQUESTS                                         = 429;   // RFC6585
    const _REQUEST_HEADER_FIELDS_TOO_LARGE                           = 431;   // RFC6585
    const _UNAVAILABLE_FOR_LEGAL_REASONS                             = 451;
    const _INTERNAL_SERVER_ERROR                                     = 500;
    const _NOT_IMPLEMENTED                                           = 501;
    const _BAD_GATEWAY                                               = 502;
    const _SERVICE_UNAVAILABLE                                       = 503;
    const _GATEWAY_TIMEOUT                                           = 504;
    const _VERSION_NOT_SUPPORTED                                     = 505;
    const _VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL                      = 506;    // RFC2295
    const _INSUFFICIENT_STORAGE                                      = 507;    // RFC4918
    const _LOOP_DETECTED                                             = 508;    // RFC5842
    const _NOT_EXTENDED                                              = 510;    // RFC2774
    const _NETWORK_AUTHENTICATION_REQUIRED                           = 511;    // RFC6585


    public static $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    );
}