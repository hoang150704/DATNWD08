<?php
 namespace Database\Factories;
 
 use App\Models\User;
 use Illuminate\Database\Eloquent\Factories\Factory;
 use Illuminate\Support\Facades\Hash;
 use Illuminate\Support\Str;
 
 class UserFactory extends Factory
 {
     protected $model = User::class;
 
     public function definition()
     {
         return [
             'name' => $this->faker->name,
             'username' => $this->faker->userName,
             'email' => $this->faker->unique()->safeEmail,
             'avatar' => $this->faker->imageUrl(200, 200, 'people'),
             'role' => \App\Models\User::ROLE_MEMBER,
             'email_verified_at' => now(),
             'created_at' => now(),
             'updated_at' => now(),
             'password' => Hash::make('password'),
             'remember_token' => Str::random(10),
             'is_active' => 1
         ];
     }
 }