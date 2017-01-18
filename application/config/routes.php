<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'health_controller';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/* 管理ユーザが扱えるAPI */
$route['admin/rooms']['post'] = 'admins_controller/create_room';
$route['admin/rooms/(:any)']['get'] = 'admins_controller/select_room/$1';
$route['admin/rooms/(:num)']['put'] = 'admins_controller/update_room/$1';
$route['admin/rooms/(:num)']['delete'] = 'admins_controller/delete_room/$1';
$route['admin/rooms/(:any)/members/all']['get'] = 'admins_controller/select_users/$1';

/* 一般ユーザが扱えるAPI */
$route['rooms']['get'] = 'rooms_controller/select_rooms';
$route['rooms/(:any)']['get'] = 'rooms_controller/select_room/$1';
$route['rooms/(:any)/members/all']['get'] = 'rooms_controller/select_users/$1';
$route['rooms/(:any)/members']['get'] = 'rooms_controller/select_user/$1';
$route['rooms/(:any)/members']['post'] = 'rooms_controller/create_user/$1';
$route['rooms/(:any)/messages/past/(:num)']['get'] = 'rooms_controller/select_messages_past/$1/$2';
$route['rooms/(:any)/messages']['get'] = 'rooms_controller/select_messages/$1';
$route['rooms/(:any)/messages/(:num)']['get'] = 'rooms_controller/select_message/$1/$2';
$route['rooms/(:any)/messages']['post'] = 'rooms_controller/create_message/$1';

/* APIのコントローラーを直接たたかせないために宣言。スラッシュで区切るパターン分の宣言が必要。 */
$route[':any'] = 'errors_controller/error_404';
$route[':any/:any'] = 'errors_controller/error_404';
$route[':any/:any/:any'] = 'errors_controller/error_404';
$route[':any/:any/:any/:any'] = 'errors_controller/error_404';
$route[':any/:any/:any/:any/:any'] = 'errors_controller/error_404';
