<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-lg">

      <!-- Logo -->
      <div class="flex justify-center mb-4">
        <img src="../../assets/images/lawofficelogo.png" alt="Logo" class="w-20 h-20 object-contain" />
      </div>

      <!-- Title -->
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-[#183e68]">
          Pineda Law Office
        </h1>
        <p class="text-gray-500 text-sm">
          Data Management System
        </p>
      </div>

      <form>
        <!-- Email -->
        <div class="mb-4">
          <label class="block text-sm font-semibold mb-2 text-gray-700">
            Email
          </label>
          <div class="relative">
            <input
              type="email"
              placeholder="Enter your email"
              class="w-full text-[#183e68] px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#183e68]"
            />
          </div>
        </div>

        <!-- Password -->
        <div class="mb-6">
          <label class="block text-sm font-semibold mb-2 text-gray-700">
            Password
          </label>

          <div class="relative">
            <input
              :type="showPassword ? 'text' : 'password'"
              placeholder="Enter your password"
              class="w-full text-[#183e68] px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#183e68]"
            />

            <!-- Eye Icon -->
            <button
              type="button"
              @click="togglePassword"
              class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-[#183e68]"
            >
              <!-- Eye Open -->
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm2.458 5.458C15.732 18.79 13.938 19.5 12 19.5c-1.938 0-3.732-.71-5.458-2.042C4.5 15.562 3 13.5 3 12s1.5-3.562 3.542-5.458C8.268 5.21 10.062 4.5 12 4.5c1.938 0 3.732.71 5.458 2.042C19.5 8.438 21 10.5 21 12s-1.5 3.562-3.542 5.458z" />
              </svg>

              <!-- Eye Slash -->
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13.875 18.825A10.05 10.05 0 0112 19.5c-4.5 0-8.25-3-9-7.5a9.956 9.956 0 012.16-4.112M6.223 6.223A9.953 9.953 0 0112 4.5c4.5 0 8.25 3 9 7.5a9.953 9.953 0 01-4.223 6.277M6.223 6.223L3 3m3.223 3.223l11.314 11.314" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Button -->
        <button
          type="submit"
          class="w-full bg-[#183e68] text-white py-2 rounded-lg font-semibold hover:opacity-90 transition duration-300"
        >
          Sign In
        </button>
      </form>

    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const showPassword = ref(false);

const togglePassword = () => {
  showPassword.value = !showPassword.value;
};
</script>
