<?php

namespace App\Http\Requests\Auction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAuctionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $auction = $this->route('auction');
        return $auction && $auction->seller_id === $this->user()->id && $auction->status === 'scheduled';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            'start_price' => 'sometimes|required|numeric|min:0',
            'bid_increment' => 'sometimes|required|numeric|min:1',
            'starts_at' => 'sometimes|required|date|after_or_equal:now',
            'ends_at' => 'sometimes|required|date|after:starts_at',
        ];
    }
}
