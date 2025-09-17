<?php

namespace App\Http\Requests;

use App\Models\Donation;
use App\Models\Medicine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DonationRequest extends FormRequest
{
    /**
     * Normalize input before validation.
     * Allows clients to send medicines[*][medicine_name] which will be
     * resolved to medicines[*][medicine_id] if uniquely found by brand_name.
     */
    protected function prepareForValidation(): void
    {
        $medicines = $this->input('medicines', []);

        if (is_array($medicines)) {
            foreach ($medicines as $i => $item) {
                $hasId = isset($item['medicine_id']) && is_numeric($item['medicine_id']);
                $name = isset($item['medicine_name']) ? trim((string) $item['medicine_name']) : '';

                if ($hasId || $name === '') {
                    continue;
                }

                // Try exact match on brand_name first
                $exact = Medicine::where('brand_name', $name)->value('id');
                if ($exact) {
                    $medicines[$i]['medicine_id'] = $exact;
                    continue;
                }

                // Fallback: single partial match
                $matches = Medicine::where('brand_name', 'like', "%{$name}%")
                    ->limit(2)
                    ->pluck('id');

                if ($matches->count() === 1) {
                    $medicines[$i]['medicine_id'] = $matches->first();
                }
            }
        }

        $this->merge(['medicines' => $medicines]);
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = Auth::id();
        $fourMonthsFromNow = now()->addMonths(4)->format('Y-m-d');

        return [
            // Required fields - now supporting multiple medicines
            'medicines' => [
                'required',
                'array',
                'min:1',
                'max:5', // Allow up to 5 medicines per donation
                // Check weekly limit (3 medicines per week total)
                function ($attribute, $value, $fail) use ($userId) {
                    // Count existing medicines donated this week
                    $weeklyMedicineCount = DB::table('donation_medicines')
                        ->join('donations', 'donation_medicines.donation_id', '=', 'donations.id')
                        ->where('donations.user_id', $userId)
                        ->where('donations.created_at', '>=', now()->subWeek())
                        ->where('donations.status', '!=', Donation::STATUS_REJECTED)
                        ->count();

                    // Add the count of medicines in current request
                    $totalMedicines = $weeklyMedicineCount + count($value);

                    if ($totalMedicines > 3) {
                        $fail('You can only donate up to 3 medicines per week. You have already donated ' . $weeklyMedicineCount . ' medicine(s) this week.');
                    }
                },
            ],
            'medicines.*.medicine_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'medicines.*.medicine_id' => [
                'required',
                'integer',
                'exists:medicines,id',
                function ($attribute, $value, $fail) {
                    $medicine = Medicine::find($value);
                    if ($medicine && $medicine->deleted_at) {
                        $fail('The selected medicine is no longer available.');
                    }
                },
                // Check for duplicate donation within 30 days
                function ($attribute, $value, $fail) use ($userId) {
                    $recentDonation = Donation::where('user_id', $userId)
                        ->whereHas('medicines', function ($query) use ($value) {
                            $query->where('medicine_id', $value);
                        })
                        ->where('created_at', '>=', now()->subDays(30))
                        ->where('status', '!=', Donation::STATUS_REJECTED)
                        ->exists();

                    if ($recentDonation) {
                        $fail('You have already donated this medicine within the last 30 days.');
                    }
                },
            ],
            'medicines.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
            'medicines.*.expiry_date' => [
                'required',
                'date',
                'after:' . $fourMonthsFromNow,
            ],
            'medicines.*.batch_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'packaging_photos' => [
                'required',
                'array',
                'min:1',
                'max:3',
            ],
            'packaging_photos.*' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120', // 5MB
            ],
            'sealed_confirmed' => [
                'required',
                'boolean',
                'accepted',
            ],
            'contact_info' => [
                'required',
                'string',
                'max:255',
            ],

        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'medicines.required' => 'Please provide at least one medicine to donate.',
            'medicines.array' => 'Medicines must be provided as an array.',
            'medicines.min' => 'Please provide at least one medicine.',
            'medicines.max' => 'You can donate maximum 5 medicines per donation.',
            'medicines.*.medicine_id.required' => 'Please select a medicine to donate.',
            'medicines.*.medicine_id.exists' => 'The selected medicine does not exist.',
            'medicines.*.quantity.required' => 'Please specify the quantity of medicine.',
            'medicines.*.quantity.min' => 'Quantity must be at least 1.',
            'medicines.*.quantity.max' => 'Quantity cannot exceed 1000.',
            'medicines.*.expiry_date.required' => 'Please provide the expiry date.',
            'medicines.*.expiry_date.after' => 'Medicine must have at least 4 months remaining before expiry.',
            'packaging_photos.required' => 'Please upload at least one photo of the medicine packaging.',
            'packaging_photos.min' => 'Please upload at least one photo.',
            'packaging_photos.max' => 'You can upload maximum 3 photos.',
            'packaging_photos.*.image' => 'Each file must be an image.',
            'packaging_photos.*.mimes' => 'Images must be in JPEG, PNG, GIF, or WebP format.',
            'packaging_photos.*.max' => 'Each image must not exceed 5MB.',
            'sealed_confirmed.required' => 'Please confirm that the medicine is sealed/unopened.',
            'sealed_confirmed.accepted' => 'You must confirm that the medicine is sealed/unopened.',
            'contact_info.required' => 'Please provide your contact information.',
            'contact_info.max' => 'Contact information cannot exceed 255 characters.',
            'medicines.*.batch_number.max' => 'Batch number cannot exceed 100 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'medicines' => 'medicines',
            'medicines.*.medicine_id' => 'medicine',
            'medicines.*.quantity' => 'quantity',
            'medicines.*.expiry_date' => 'expiry date',
            'medicines.*.batch_number' => 'batch number',
            'packaging_photos' => 'packaging photos',
            'packaging_photos.*' => 'packaging photo',
            'sealed_confirmed' => 'sealed confirmation',
            'contact_info' => 'contact information',
        ];
    }
}
