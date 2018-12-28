<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//US1
Route::get('items', 'ItemsControllerAPI@index');
Route::get('items/paginate', 'ItemsControllerAPI@paginate');

Auth::routes(['verify' => true]);


//Route::put('users/{id}/password', 'UserControllerAPI@updatePassword');
//US3
Route::post('login', 'LoginControllerAPI@login');
Route::middleware('auth:api')->post('logout', 'LoginControllerAPI@logout');
Route::middleware('auth:api')->get('users/me', 'UserControllerAPI@myProfile');
//US4
Route::middleware('auth:api')->put('users/{id}/password', 'UserControllerAPI@updatePassword');
//US5
Route::middleware('auth:api')->put('users/{id}', 'UserControllerAPI@update');
Route::middleware('auth:api')->post('users/{id}/uploadFile', 'UserControllerAPI@uploadFile');

//US9
Route::middleware('auth:api','cook')->get('users/cook/{id}/orders', 'UserControllerAPI@cookOrders');
//US11
Route::middleware('auth:api')->put('order/{id}', 'OrderControllerAPI@update');
Route::middleware('auth:api', 'waiter')->put('meals/{id}', 'MealControllerAPI@update');
//US12
Route::middleware('auth:api', 'waiter')->get('tables', 'RestaurantTablesControllerAPI@mesasLivres');
Route::middleware('auth:api', 'waiter')->post('meals', 'MealControllerAPI@store');
//US13
Route::middleware('auth:api', 'waiter')->get('users/{id}/meals', 'UserControllerAPI@meals');
Route::middleware('auth:api', 'waiter')->post('orders/create', 'OrderControllerAPI@store');
//US14
Route::middleware('auth:api', 'waiter')->get('users/waiter/{id}/pendingOrders', 'UserControllerAPI@waiterPendingOrders');
//US15
Route::middleware('auth:api', 'waiter')->delete('orders/{id}', 'OrderControllerAPI@destroy');
//US17
Route::middleware('auth:api', 'waiter')->get('users/waiter/{id}/preparedOrders', 'UserControllerAPI@waiterPreparedOrders');
//US19
Route::middleware('auth:api', 'waiter')->get('meals/{id}', 'MealControllerAPI@summary');
Route::middleware('auth:api', 'waiter')->get('meals/{id}/items', 'MealControllerAPI@items');
//US22
Route::get('cashier','UserControllerAPI@invoices');

//US24
Route::get('cashier_all','UserControllerAPI@invoices_all');



Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
	
Route::middleware(['auth:api', 'manager'])->get('managers','ManagerControllerAPI@index');
    Route::middleware('auth:api', 'manager')->get('managers','ManagerControllerAPI@itemsDataTable');

});


// US28
Route::middleware(['auth:api', 'manager'])->get('manager/itemsDataTable','ManagerControllerAPI@itemsDataTable');
Route::middleware(['auth:api', 'manager'])->delete('manager/managerItemList/{id}', 'ManagerControllerAPI@destroyItem');
Route::middleware(['auth:api', 'manager'])->post('manager/editItem/{id}/uploadFile', 'ManagerControllerAPI@uploadFile');
Route::middleware(['auth:api', 'manager'])->put('manager/editItem/{id}', 'ManagerControllerAPI@update');
Route::middleware(['auth:api', 'manager'])->post('manager/createItem', 'ManagerControllerAPI@store');
Route::middleware(['auth:api', 'manager'])->get('manager/tablesDataTable','ManagerControllerAPI@tablesDataTable');
Route::middleware(['auth:api', 'manager'])->delete('manager/managerTableList/{id}', 'ManagerControllerAPI@destroyTable');
Route::middleware(['auth:api', 'manager'])->post('manager/createTable', 'ManagerControllerAPI@storeTable');

//US29
Route::middleware(['auth:api', 'manager'])->get('manager/usersDataTable','ManagerControllerAPI@userDataTable');
Route::middleware(['auth:api', 'manager'])->post('users/create', 'UserControllerAPI@store');
Route::middleware(['auth:api', 'manager'])->put('manager/editUser/{id}', 'ManagerControllerAPI@updateUser');
//US30
Route::middleware(['auth:api', 'manager'])->delete('manager/managerUserList/{id}', 'ManagerControllerAPI@destroyUser');
//US31
Route::middleware(['auth:api', 'manager'])->get('manager/invoiceDataTable', 'ManagerControllerAPI@invoiceDataTable');
Route::middleware(['auth:api', 'manager'])->get('manager/mealDataTable', 'ManagerControllerAPI@mealDataTable');
Route::middleware(['auth:api', 'manager'])->get('manager/orderDataTable', 'ManagerControllerAPI@orderDataTable');

