<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Elliptic\EC;
use kornrunner\Keccak;

class AutController extends Controller
{
    public function getNonce(Request $request)
    {
        $request->validate([
            'address' => ['required']
        ]);

        $user = User::firstOrCreate(
            ['address' =>  $request->input('address')],
            ['nonce' => Str::uuid()]
        );

        $msg = "Sign this message to validate that you are the owner of the account. Random string: " . $user->nonce;

        return response()->json([
            'sign_message' => $msg
        ]);
    }

    public function authWeb3(Request $request)
    {
        $address = $request->input('address');

        $user = User::where('address', $address)->first();

        if (!$user) {
            abort(401);
        }

        $params = [
            'address' => $request->input('address'),
            'signature' => $request->input('signature'),
            'message' => "Sign this message to validate that you are the owner of the account. Random string: " . $user->nonce,
        ];

        if (!$this->authenticate($params)) {
            throw ValidationException::withMessages([
                'signature' => 'Invalid signature.'
            ]);
        }

        $token = auth()->tokenById($user->id);

        $ttl = auth()->setToken($token)->factory()->getTTL() * 60;

        $user->nonce = Str::uuid();
        $user->save();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'associated_user' => $user,
            'expires_in' => $ttl,
        ]);
    }

    protected function authenticate(array $params): bool
    {
        return $this->verifySignature(
            $params['message'],
            $params['signature'],
            $params['address'],
        );
    }

    protected function pubKeyToAddress($pubkey)
    {
        return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
    }

    protected function verifySignature($message, $signature, $address)
    {
        $msglen = strlen($message);
        $hash   = Keccak::hash("\x19Ethereum Signed Message:\n{$msglen}{$message}", 256);
        $sign   = [
            "r" => substr($signature, 2, 64),
            "s" => substr($signature, 66, 64)
        ];
        $recid  = ord(hex2bin(substr($signature, 130, 2))) - 27;
        if ($recid != ($recid & 1))
            return false;

        $ec = new EC('secp256k1');
        $pubkey = $ec->recoverPubKey($hash, $sign, $recid);

        return Str::lower($address) === $this->pubKeyToAddress($pubkey);
    }
}
