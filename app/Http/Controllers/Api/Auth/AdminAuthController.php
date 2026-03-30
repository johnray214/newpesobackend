<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!in_array($user->role, ['admin', 'staff'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
            'message' => 'Login successful',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $status = \Illuminate\Support\Facades\Password::broker('users')->sendResetLink($request->only('email'));

        if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            return response()->json(['success' => true, 'message' => 'Password reset link sent to your email.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send reset link.'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = \Illuminate\Support\Facades\Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => \Illuminate\Support\Facades\Hash::make($password)
                ])->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();
            }
        );

        if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            return response()->json(['success' => true, 'message' => 'Password has been securely reset.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to reset password, link might be invalid or expired.'], 400);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'first_name'  => $user->first_name ?? '',
                'last_name'   => $user->last_name  ?? '',
                'middle_name' => $user->middle_name ?? '',
                'email'       => $user->email,
                'sex'         => $user->sex ? ucfirst($user->sex) : '',
                'contact'     => $user->contact ?? '',
                'address'     => $user->address  ?? '',
                'role'        => ucfirst($user->role   ?? 'admin'),
                'status'      => ucfirst($user->status ?? 'active'),
                'photo'       => $user->photo ?? null,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name'  => 'sometimes|string|max:100',
            'last_name'   => 'sometimes|string|max:100',
            'middle_name' => 'sometimes|nullable|string|max:100',
            'email'       => 'sometimes|email|unique:users,email,' . $user->id,
            'sex'         => 'sometimes|nullable|in:Male,Female,male,female',
            'contact'     => 'sometimes|nullable|string|max:20',
            'address'     => 'sometimes|nullable|string|max:255',
        ]);

        // DB enum uses lowercase; normalize before saving
        if (isset($data['sex']))     $data['sex']    = strtolower($data['sex']);

        $user->update($data);
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data'    => $user->fresh(),
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string',
            'password'              => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = $request->user();

        // Delete old stored photo if it exists
        if ($user->photo) {
            $oldPath = str_replace(asset('storage/'), '', $user->photo);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $path = $request->file('photo')->store('profile-photos', 'public');
        $url  = asset('storage/' . $path);

        $user->update(['photo' => $url]);

        return response()->json([
            'success' => true,
            'message' => 'Photo updated successfully.',
            'data'    => ['photo' => $url],
        ]);
    }
}
