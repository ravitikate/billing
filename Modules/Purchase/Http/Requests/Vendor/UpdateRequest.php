<?php

namespace Modules\Purchase\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rules()
    {

        return [
            'primary_name' => 'required',
              
            'email' => 'required',
                        'employee_login_id' => 'required',
                        // 'employee_login_id1' => 'required|unique:users,employee_login_id,null,id,company_id,' . company()->id.'|max:100',

                        // 'email' => 'required|email:rfc,strict|unique:users,email,null,id,company_id,' . company()->id.'|max:100',

            'website' => 'nullable|url'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

}
