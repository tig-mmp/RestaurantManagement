<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvoiceItems as InvoiceItemResource;
use App\Http\Resources\Invoice as InvoiceFilted;
use App\Http\Resources\OrderItem as OrderItemResource;
use App\Http\Resources\Order as OrderResource;
use App\Http\Resources\Meal as MealResource;
use App\Invoice;
use App\InvoiceItem as invoicesitmes;
use App\InvoiceItem;
use App\Meal;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\copy_to_string;
use http\Client\Response;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Jsonable;

use Illuminate\Support\Facades\Hash;
use App\Http\Resources\User as UserResource;
use Illuminate\Support\Facades\DB;

use App\User;
use App\Item as Items;
use App\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Mail\SendMailable;

class UserControllerAPI extends Controller
{

    public function mail($email)
    {
        $user = User::where('email', $email)->select('id', 'email')->first();
        $link = $_SERVER['SERVER_NAME'] . '/changePassword/' . $user->id;
        Mail::to($user->email)->send(new SendMailable($user->name, $link));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|min:3',
            'username' => 'required|unique:users,username,'.$id
        ]);
        $user = User::findOrFail($id);
        $old_shift = $user->shift_active;
        $user->fill($request->all());
        if ($old_shift === 1 && $request->get('shift_active') === 0) {
            $user->fill(['last_shift_end' => Carbon::now()->toDateTimeString(),]);
        }
        if ($old_shift === 0 && $request->get('shift_active') === 1) {
            $user->fill(['last_shift_start' => Carbon::now()->toDateTimeString(),]);
        }
        $user->save();
        return new UserResource($user);
    }

    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'image',
        ]);
        if(isset($_FILES['file']) && $_FILES['file']['size'] > 5242880) {
            return response()->json(array(
                'message' => 'The given data was invalid.',
                'errors' => "max size of image is 5MB"
            ),422);
        }
        $user = User::findOrFail($id);
        if($user->profile_photo != null){
            Storage::delete("public/profiles/{$user->profile_photo}");
        }
        $filename = basename($request->file('file')->store('public/profiles'));
        $user->fill([
            'photo_url' => $filename,
        ]);
        $user->save();
        return new UserResource($user);
    }

    public function updatePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if (!Hash::check($request['old_password'], $user->password)){
            return response()->json(array(
                'message' => 'The given data was invalid.',
                'errors' => array('password' => "old_password and actual_password don't match")
            ),422);
        }
        $request->validate([
            'password' => 'confirmed|min:3|different:old_password',
        ]);
        $user->update(['password' => Hash::make($request['password'])]);
        return response()->json(array(new UserResource($user),$request['password'], Hash::make($request['password'])) , 201);
    }

    public function updatePasswordFirstTime(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->password != 'a'){
            return response()->json('User already have a password',422);
        }
        $request->validate([
            'password' => 'min:3',
        ]);
        $user->update(['password' => Hash::make($request['password'])]);
        return response()->json(array(new UserResource($user),$request['password'], Hash::make($request['password'])) , 201);
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|min:3',
            'username' => 'required|min:3|unique:users,username',
            'email' => 'required|email|unique:users,email'
        ]);
        $user = new User();
        $user->fill($request->all());
        $user->fill(['password' => 'a']);
        $user->save();

        //send mail

        $this->mail($user->email);
        return response()->json(new UserResource($user), 201);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(null, 204);
    }

    //waiter
    public function cookOrders(Request $request, $id)
    {
        return OrderItemResource::collection(Order::where('state', 'confirmed')
            ->orWhere('responsible_cook_id', $id)->where('state', 'in preparation')
            ->orderByRaw("FIELD(state, 'in prepatation', 'confirmed')")->orderBy('start')->get());
    }

    public function meals(Request $request, $id)
    {
        return MealResource::collection(Meal::where('responsible_waiter_id', '=', $id)->where('state', 'active')->get());
    }

    public function waiterPendingOrders(Request $request, $id)
    {
        return OrderItemResource::collection(Order::join('meals', 'orders.meal_id', 'meals.id')
            ->where('responsible_waiter_id', $id)->select('orders.*')->distinct('item_id')
            ->whereIn('orders.state', ['pending', 'confirmed'])->orderBy('orders.state')
            ->orderBy('orders.start')->get());
        /*
        return OrderItemResource::collection(
            Order::with(['meal' => function($query) use($id){
                return $query->with('table:table_number')
                    ->select('id', 'responsible_waiter_id', 'table_number')
                    ->where('responsible_waiter_id', $id);
            }])
                ->select('id', 'meal_id', 'start', 'state', 'item_id', 'responsible_cook_id')
                ->whereIn('orders.state', ['pending', 'confirmed'])
                ->orderBy('orders.state')
                ->orderBy('orders.start')
                ->get());
        */
    }

    public function waiterPreparedOrders(Request $request, $id)
    {
        return OrderItemResource::collection(Order::with('meal')
            ->whereHas('meal', function($query) use($id){
                return $query->with('waiter:id,name')
                    ->with('table:table_number')
                    ->select('id', 'responsible_waiter_id', 'table_number')
                    ->where('responsible_waiter_id', $id);
            })
            ->select('id', 'meal_id', 'start', 'state', 'item_id', 'responsible_cook_id')
            ->where('orders.state', 'prepared')
            ->orderBy('orders.start')->get());
    }

    public function invoices(Request $request)
    {
        $query1 = InvoiceFilted::collection(Invoice::with('meal.waiter')->where('state', 'pending')->paginate(3));
        /*
         * Igual a de cima se usar esta tem-se de alterar depois a paginação e tabela
         * $query = DB::table('invoices')
            ->join('meals', 'invoices.meal_id', '=', 'meals.id')
            ->join('users', 'meals.responsible_waiter_id', '=', 'users.id')
            ->select( 'meals.table_number', 'users.name as waiter','invoices.total_price',
                'invoices.id', 'invoices.name', 'invoices.nif', 'invoices.meal_id')
            ->where('invoices.state', '=', 'pending')
            ->paginate(25);*/
        return $query1;
    }
    public function invoices_all(Request $request)
    {
        /* $query = Meal::where('state', 'not paid')->get();*/
    /*    $query1 = DB::table('items')
            ->join('invoice_items', 'items.id', '=', 'invoice_items.item_id')
            ->select(DB::raw("CONCAT(items.name,' quantity:' , invoice_items.quantity, ' unit price:', 
                invoice_items.unit_price, ' total price:', invoice_items.sub_total_price ) as item"))
            ->distinct('item')->get();
       // dd($query1);
        $query4 = DB::table('invoice_items')
            ->select('invoice_id','item_id')->distinct('invoice_id')->paginate(25);
*/


    //$query2 = InvoiceItemResource::collection(InvoiceItem::with('item')->with('invoice.meal.waiter')->get());
  /*  $query3 = InvoiceItem::join('invoices','invoices.id','invoice_items.invoice_id')
        ->join('meals','invoices.meal_id','meals.id')->join('users','users.id','meals.responsible_waiter_id')
        ->join('items','items.id','invoice_items.item_id')->select('invoices.date','meals.table_number','users.name','invoices.total_price',
            'invoice_items.quantity','items.name as item','invoice_items.unit_price','invoice_items.sub_total_price')->
        distinct('meals.table_number','users.name')->groupBy('invoice_id','invoice_items.item_id','users.name')->paginate(30);
   */ $query5 = InvoiceItemResource::collection(Invoice::with('meal.waiter')->with('invoice_items')->with('invoice_items.item')->paginate(5));

       // ->distinct('users.name')
        return $query5;
    }
//DB::raw("(Select items.name from items where items.id=invoice_items.item_id) as items")
/* $query = DB::table('invoices')
            ->join('meals', 'invoices.meal_id', '=', 'meals.id')
            ->join('users', 'meals.responsible_waiter_id', '=', 'users.id')
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('items', 'items.id', '=', 'invoice_items.item_id')
            ->select('invoices.date','meals.table_number', 'users.name','invoices.total_price')
            ->groupBy(  'invoices.id')

            ->paginate(25);
primeira parte completa
*/

    public function myProfile(Request $request)
    {
        return new UserResource($request->user());
    }
}
