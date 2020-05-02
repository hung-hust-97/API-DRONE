<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use DB;
use App\APIReturnHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Resources\UserResource as UserResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use GuzzleHttp\Client;
use Laravel\Passport\Client as OClient; 
use Exception;

use function PHPSTORM_META\type;

class UserController extends Controller
{
    public $successStatus = 200;
    

    public function requestRegister(Request $request) { 
        $validator = Validator::make($request->all(),[
                            'name'=>'required',
                            'phone'=>'required',
                            'email'=>['required','unique:users','regex:/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/'],
                            // 'avatar'=>'required',
                            'address'=>'required',
                            'user'=>['required','unique:users'],
                            'password'=>'required',
                        ],[
                            'name.required'=>'Họ và tên không được để trống',
                            'phone.required'=>'Số điện thoại không được để trống',
                            // 'avatar.required' => 'Số chứng minh nhân dân không được để trống',
                            'user.unique' => 'User này đã được sử dụng',
                            'address' => 'Địa chỉ không được trống',
                            'email.unique'=>'Địa chỉ email này đã được sử dụng',
                            'password' => 'Mật khẩu không được trống',
        
                        ]);

        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        $password = $request->password;
        $input = $request->all(); 
        $input['password'] = bcrypt($input['password']); 
        $user = User::create($input); 
        //Send email
        // if ((!empty($member))&&(!empty($params['email']))) {

        //     Mail::to($params['email'])->send(new RegisterMember(array(
        
        //         'name'=>$params['name'],
        //         'email' => $params['email'],
        //         'temp_password' => $params['password']
        //     )));
        // }

        $oClient = OClient::where('password_client', 1)->first();
        return $this->getTokenAndRefreshToken($oClient, $user->email, $password);
    }
    public function loginPost(Request $request) { 
        $params = $request->all();
        $validator = Validator::make($params,[
                'email'=>['required','regex:/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/'],
                // 'user' => 'required',
                'password' => 'required',

            ],[
                // 'email.required'=>'Email không được để trống',
                // 'email.regex' => "Email không đúng định dạng",
                'user.required'=>'User không được để trống',
                'password.required' => 'Password không được để trống'
            ]);

            if($validator->fails()){
                $helper = new APIReturnHelper();
                return array(
                    'success' => false,
                    'errors' => $helper->getMessageErrors($validator->errors())
                );
            }
        if (Auth::attempt(['email' => $params['email'], 'password' => $params['password']])) { 
            $oClient = OClient::where('password_client', 1)->first();
            return $this->getTokenAndRefreshToken($oClient,$params['email'], $params['password']);
        } 
        else { 
            return response()->json(['error'=>'Unauthorised'], 401); 
        } 
    }
    public function getUser(Request $request) { 
        $validator = Validator::make($request->all(),[
            'id'=>'required',
        ],[
            'id.required'=>'id không được để trống',
        ]);

    if ($validator->fails()) { 
        return response()->json(['error'=>$validator->errors()], 401);            
    }

        $user = DB::table('users')->where('id',$request->id)->first();
        if(is_null($user)){
            return response()->json([
                "error" => "id not found"
            ], 404);
        }
        return response()->json([
            "id"  =>  $user->id,
            "user" => $user->user,
            "name" => $user->name,
            "avatar" => $user->avatar,
            "phone" => $user->phone,
            "address" => $user->address,
            "mail" => $user->email,
        ], $this->successStatus);
    }

    //logout
    public function logout(Request $request) {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ],200);
    }

    public function unauthorized() { 
        return response()->json("unauthorized", 401); 
    }
    public function getMessageErros($errors){
        $result = array();
        if(!empty($errors)){
            foreach ($errors->getMessages() as $key=>$value){
                $result[] = $value[0];
            }
        }
        return implode('; ',$result);
    }

    public function getTokenAndRefreshToken(OClient $oClient, $email, $password) { 
        $oClient = OClient::where('password_client', 1)->first();
        $http = new Client;
        // Tạo và refresh token API
        $response = $http->request('POST', 'http://localhost/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $oClient->id,
                'client_secret' => $oClient->secret,
                'username' => $email,
                'password' => $password,
                'scope' => '*',
            ],
        ]);

        // CHuyển từ Json sang mảng với true, false thì sang object
        $array1 = json_decode((string) $response->getBody(), true);
        $user = Auth::user();
        if(is_null($user)){
            $user = DB::table('users')->where('email', $email)->first();
            $array2 = json_decode(json_encode($user),true);
        }
        else{
            $array2 = json_decode((string)$user,true);
        }     
        $result=array_merge($array1, $array2);
        $kt=' ';
        $token = $result['token_type'].$kt.$result['access_token'];
        return response()->json(
            [
                'id' => $result['id'],
                'user' => $result['user'],
                'name' => $result['name'],
                'avatar' => $result['avatar'],
                'address' => $result['address'],
                'phone' => $result['phone'],
                'email' => $result['email'],
                'token' => $token,
                'refresh_token' =>$result['refresh_token'],
            ],
            $this->successStatus
        );
    }

    public function refreshToken(Request $request) { 
        $refresh_token = $request->header('Refreshtoken');
        $oClient = OClient::where('password_client', 1)->first();
        $http = new Client;

        try {
            $response = $http->request('POST', 'http://localhost/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refresh_token,
                    'client_id' => $oClient->id,
                    'client_secret' => $oClient->secret,
                    'scope' => '*',
                ],
            ]);
            return json_decode((string) $response->getBody(), true);
        } catch (Exception $e) {
            return response()->json("unauthorized", 401); 
        }
    }
}
