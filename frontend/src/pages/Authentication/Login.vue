<template>
  <div 
    class="min-h-screen flex items-center justify-center bg-cover bg-center bg-no-repeat p-4"
    :style="{ backgroundImage: 'url(' + backgroundImage + ')' }"
  >
    <!-- Floating Glass Card -->
    <div class="w-full max-w-md">
      <!-- Outer shadow for floating effect -->
      <div class="relative">
        <!-- Soft shadow for depth -->
        <div class="absolute inset-0 bg-black/20 rounded-2xl blur-xl transform translate-y-2"></div>
        
        <!-- Main Glass Card -->
        <div 
          class="relative backdrop-blur-md rounded-2xl shadow-2xl p-8 border"
          :style="{
            backgroundColor: 'rgba(255, 255, 255, 0.15)',
            borderColor: 'rgba(255, 255, 255, 0.3)',
            boxShadow: '0 20px 40px -10px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1) inset'
          }"
        >
          <!-- Inner glow -->
          <div class="absolute inset-0 rounded-2xl pointer-events-none" 
               :style="{ background: 'radial-gradient(circle at 50% 0%, rgba(255,255,255,0.3), transparent 70%)' }">
          </div>

          <!-- Content with higher opacity for readability -->
          <div class="relative z-10">
            <!-- Logo -->
            <div class="flex justify-center mb-4">
              <div class="relative">
                <!-- Logo glow -->
                <div class="absolute inset-0 bg-white/30 blur-xl rounded-full"></div>
                <img
                  src="../../assets/images/lawofficelogo.png"
                  alt="Logo"
                  class="relative w-24 h-24 object-contain drop-shadow-lg"
                  :style="{ filter: 'drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2))' }"
                />
              </div>
            </div>

            <!-- Title -->
            <div class="text-center mb-8">
              <h1 class="text-3xl font-bold mb-1" 
                  :style="{ 
                    color: 'white',
                    textShadow: '0 2px 4px rgba(0, 0, 0, 0.3)'
                  }">
               NICOLAS PINEDA
              </h1>
                  <h1 class="text-3xl font-bold mb-1" 
                  :style="{ 
                    color: 'white',
                    textShadow: '0 2px 4px rgba(0, 0, 0, 0.3)'
                  }">
                LAW OFFICE 
              </h1>
              <p class="text-sm tracking-wide"
                 :style="{ 
                   color: 'rgba(255, 255, 255, 0.9)',
                   textShadow: '0 1px 2px rgba(0, 0, 0, 0.2)'
                 }">
                Data Management System
              </p>
            </div>

            <form @submit.prevent="handleLogin" class="space-y-6">
              <!-- Email Field with Error -->
              <div>
                <label class="block text-sm font-medium mb-1" 
                       :style="{ color: 'rgba(255, 255, 255, 0.9)' }">
                  Email
                </label>
                <input
                  v-model="email"
                  type="email"
                  class="w-full px-4 py-3 rounded-xl transition-all duration-200 placeholder-white/50"
                  :class="{ 'border-red-500': errors.email }"
                  :style="{
                    backgroundColor: 'rgba(255, 255, 255, 0.15)',
                    border: errors.email ? '1px solid #ef4444' : '1px solid rgba(255, 255, 255, 0.3)',
                    color: 'white',
                    outline: 'none',
                    backdropFilter: 'blur(5px)'
                  }"
                  placeholder="Enter your email"
                  @focus="handleFocus"
                  @blur="handleBlur"
                  @input="clearFieldError('email')"
                />
                <!-- Email Error Message -->
                <p v-if="errors.email" class="mt-1 text-sm text-red-300" 
                   :style="{ textShadow: '0 1px 2px rgba(0, 0, 0, 0.2)' }">
                  {{ errors.email }}
                </p>
              </div>

              <!-- Password Field with Error -->
              <div>
                <label class="block text-sm font-medium mb-1" 
                       :style="{ color: 'rgba(255, 255, 255, 0.9)' }">
                  Password
                </label>
                <div class="relative">
                  <input
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    class="w-full px-4 py-3 rounded-xl transition-all duration-200 placeholder-white/50"
                    :class="{ 'border-red-500': errors.password }"
                    :style="{
                      backgroundColor: 'rgba(255, 255, 255, 0.15)',
                      border: errors.password ? '1px solid #ef4444' : '1px solid rgba(255, 255, 255, 0.3)',
                      color: 'white',
                      outline: 'none',
                      backdropFilter: 'blur(5px)'
                    }"
                    placeholder="Enter your password"
                    @focus="handleFocus"
                    @blur="handleBlur"
                    @input="clearFieldError('password')"
                  />
                  <button
                    type="button"
                    @click="togglePasswordVisibility"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 transition-colors duration-200"
                    :style="{ color: 'rgba(255, 255, 255, 0.7)' }"
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
                <!-- Password Error Message -->
                <p v-if="errors.password" class="mt-1 text-sm text-red-300"
                   :style="{ textShadow: '0 1px 2px rgba(0, 0, 0, 0.2)' }">
                  {{ errors.password }}
                </p>
              </div>

              <!-- Login Button -->
              <button
                type="submit"
                :disabled="loading"
                class="relative w-full py-3 rounded-xl font-medium transition-all duration-200 overflow-hidden"
                :style="{
                  background: 'linear-gradient(135deg, rgba(26, 73, 114, 0.9), rgba(15, 47, 74, 0.95))',
                  color: 'white',
                  border: '1px solid rgba(255, 255, 255, 0.2)',
                  backdropFilter: 'blur(5px)',
                  boxShadow: '0 4px 15px rgba(0, 0, 0, 0.3)',
                  opacity: loading ? 0.7 : 1
                }"
                @mouseover="hoverButton = true"
                @mouseleave="hoverButton = false"
              >
                <!-- Button hover effect -->
                <div class="absolute inset-0 transition-opacity duration-200"
                     :style="{ 
                       opacity: hoverButton ? 1 : 0,
                       background: 'linear-gradient(135deg, rgba(255, 255, 255, 0.2), transparent)'
                     }">
                </div>
                
                <span class="relative z-10 flex items-center justify-center">
                  <svg v-if="loading" class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ loading ? "Logging in..." : "Login" }}
                </span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import { login } from "@/services/auth";
import backgroundImg from "../../assets/images/bg.jpg";

const backgroundImage = ref(backgroundImg);
const router = useRouter();

const email = ref("");
const password = ref("");
const loading = ref(false);
const showPassword = ref(false);
const hoverButton = ref(false);

// Error handling with field-specific errors
const errors = reactive({
  email: "",
  password: "",
  general: ""
});

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const handleFocus = (event) => {
  event.target.style.backgroundColor = 'rgba(255, 255, 255, 0.25)';
  event.target.style.borderColor = 'rgba(255, 255, 255, 0.5)';
};

const handleBlur = (event) => {
  if (!errors[event.target.type === 'email' ? 'email' : 'password']) {
    event.target.style.backgroundColor = 'rgba(255, 255, 255, 0.15)';
    event.target.style.borderColor = 'rgba(255, 255, 255, 0.3)';
  }
};

// Clear specific field error when user starts typing
const clearFieldError = (field) => {
  errors[field] = "";
  errors.general = "";
};

// Validate form before submission
const validateForm = () => {
  let isValid = true;
  
  // Clear previous errors
  errors.email = "";
  errors.password = "";
  errors.general = "";
  
  // Email validation
  if (!email.value) {
    errors.email = "Email is required";
    isValid = false;
  } else if (!/\S+@\S+\.\S+/.test(email.value)) {
    errors.email = "Please enter a valid email address";
    isValid = false;
  }
  
  // Password validation
  if (!password.value) {
    errors.password = "Password is required";
    isValid = false;
  } else if (password.value.length < 6) {
    errors.password = "Password must be at least 6 characters";
    isValid = false;
  }
  
  return isValid;
};

async function handleLogin() {
  // Validate form first
  if (!validateForm()) {
    return;
  }
  
  loading.value = true;
  
  try {
    const response = await login({
      email: email.value,
      password: password.value,
    });
    
    const userRole = response.user?.role?.name;
    
      router.push("/dashboard");
    
  } catch (err) {
    // Handle different types of errors
    if (err.message === "Invalid credentials or inactive account") {
      errors.general = "Invalid email or password";
      errors.email = "Please check your email";
      errors.password = "Please check your password";
    } else if (err.message === "Network Error") {
      errors.general = "Network error. Please check your connection.";
    } else {
      errors.general = err.message || "An error occurred during login";
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.min-h-screen::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  pointer-events: none;
}

/* Ensure the card stays above the overlay */
.w-full.max-w-md {
  position: relative;
  z-index: 10;
}

/* Smooth transitions */
input, button, a {
  transition: all 0.2s ease;
}

/* Placeholder styling */
input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

/* Error state styling */
input.border-red-500 {
  border-color: #ef4444 !important;
}

/* Error message styling */
.text-red-300 {
  color: #fca5a5;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  padding-left: 0.25rem;
}

/* Remove spin buttons from number input */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}

/* Animation for error messages */
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
  20%, 40%, 60%, 80% { transform: translateX(2px); }
}

.text-red-300 {
  animation: shake 0.5s ease-in-out;
}
</style>