<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SystemSettingController extends Controller
{
    /**
     * Get system settings (Super Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $settings = [
            'whatsapp_number' => SystemSetting::getSetting('whatsapp_number', '919999999999'),
            'contact_phone' => SystemSetting::getSetting('contact_phone', '+91 99999 99999'),
            'contact_email' => SystemSetting::getSetting('contact_email', 'support@eduvorax.com'),
            'founding_offer_text' => SystemSetting::getSetting('founding_offer_text', 'Get EduvoraX school management software free for your first year as part of our founding-school program.'),
        ];

        return response()->json(['settings' => $settings]);
    }

    /**
     * Update system settings (Super Admin only).
     */
    public function update(Request $request): JsonResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $request->validate([
            'whatsapp_number' => 'nullable|string|max:20',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:100',
            'founding_offer_text' => 'nullable|string|max:500',
        ]);

        if ($request->has('whatsapp_number')) {
            $cleanWhatsapp = preg_replace('/[^0-9]/', '', (string) $request->input('whatsapp_number'));
            SystemSetting::setSetting('whatsapp_number', $cleanWhatsapp);
        }
        if ($request->has('contact_phone')) {
            SystemSetting::setSetting('contact_phone', $request->input('contact_phone'));
        }
        if ($request->has('contact_email')) {
            SystemSetting::setSetting('contact_email', $request->input('contact_email'));
        }
        if ($request->has('founding_offer_text')) {
            SystemSetting::setSetting('founding_offer_text', $request->input('founding_offer_text'));
        }

        return response()->json([
            'success' => true,
            'message' => 'System and contact settings updated successfully!'
        ]);
    }
}
