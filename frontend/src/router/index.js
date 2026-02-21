import { createRouter, createWebHistory } from "vue-router";
import Login     from "@/pages/Authentication/Login.vue";
import Dashboard from "@/pages/Dashboard.vue";
import AdminUserManagement from "@/pages/Admin/AdminUserManagement.vue";
import AuditTrail from "../pages/Admin/AuditTrail.vue";
import AccountPage from "@/pages/AccountPage.vue";
const routes = [
  {
    path: "/",
    name: "Login",
    component: Login,
    meta: { guest: true },
  },
  {
    path: "/account",
    name: "AccountPage",
    component: AccountPage,
    meta: { requiresAuth: true },
  },
  {
    path: "/usermanagement",
    name: "AdminUserManagement",
    component: AdminUserManagement,
    meta: { requiresAuth: true , role: "admin"},
  },
  {
    path: "/audittrail",
    name: "AuditTrail",
    component: AuditTrail,
    meta: { requiresAuth: true },
  },
  {
    path: "/dashboard",
    name: "Dashboard",
    component: Dashboard,
    meta: { requiresAuth: true },
  },
  {
    path: "/:pathMatch(.*)*",
    redirect: "/dashboard",
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

function getUserRole() {
  try {
    return JSON.parse(localStorage.getItem("user"))?.role?.name ?? null;
  } catch {
    return null;
  }
}

// ─── Navigation Guard ─────────────────────────────────
// router.beforeEach((to, from, next) => {
//   const isAuthenticated = !!localStorage.getItem("token");
//   const userRole = getUserRole();

//   // Not logged in → trying to access protected route
//   if (to.meta.requiresAuth && !isAuthenticated) {
//     return next("/");
//   }

//   // Already logged in → trying to access login page
//   if (to.meta.guest && isAuthenticated) {
//     return next("/dashboard");
//   }

//   next();
// });


router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem("token");
  const userRole = getUserRole();

  // Not logged in → trying to access protected route
  if (to.meta.requiresAuth && !isAuthenticated) {
    return next("/");
  }

  // Already logged in → trying to access login page
  if (to.meta.guest && isAuthenticated) {
    return next("/dashboard");
  }

  // ✅ Route requires a specific role but user doesn't have it
  if (to.meta.role && to.meta.role !== userRole) {
    return next("/dashboard"); // redirect back to their dashboard
  }

  next();
});
export default router;