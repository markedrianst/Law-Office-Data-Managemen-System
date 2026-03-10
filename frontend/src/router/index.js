// src/router/index.js
import { createRouter, createWebHistory } from "vue-router";
import Login from "@/pages/Authentication/Login.vue";
import Dashboard from "@/pages/Dashboard.vue";
import AdminUserManagement from "@/pages/Admin/AdminUserManagement.vue";
import AuditTrail from "@/pages/Admin/AuditTrail.vue";
import AccountPage from "@/pages/AccountPage.vue";
import CaseMaster from "@/pages/Admin/cases/CaseMaster.vue";
import CaseCategory from "@/pages/Admin/MasterData/CasesCategory.vue";
import CourtMaster from "@/pages/Admin/MasterData/CourtMaster.vue";
import Documents from "@/pages/Admin/MasterData/Documents.vue";
import LawyerDashboard from "@/pages/Lawyer/LawyerDashboard.vue";
import Approvals from "@/pages/Admin/Approvals.vue";

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
    path: "/approvals",
    name: "Approvals",
    component: Approvals,
    meta: { 
      requiresAuth: true, 
      roles: ["admin", "lawyer"]
    },
  },
  {
    path: "/lawyer-dashboard",
    name: "LawyerDashboard",
    component: LawyerDashboard,
    meta: { 
      requiresAuth: true, 
      roles: ["lawyer"]
    },
  },
  {
    path: "/usermanagement",
    name: "AdminUserManagement",
    component: AdminUserManagement,
    meta: { 
      requiresAuth: true, 
      roles: ["admin"] 
    },
  },
  {
    path: "/courtmaster",
    name: "CourtMaster",
    component: CourtMaster,
    meta: { 
      requiresAuth: true, 
      roles: ["admin"] 
    },
  },
  {
    path: "/casecategory",
    name: "CaseCategory",
    component: CaseCategory,
    meta: { 
      requiresAuth: true, 
      roles: ["admin"] 
    },
  },
  {
    path: "/documents",
    name: "Documents",
    component: Documents,
    meta: { 
      requiresAuth: true, 
      roles: ["admin"] 
    },
  },
  {
    path: "/casemaster",
    name: "CaseMaster",
    component: CaseMaster,
    meta: { 
      requiresAuth: true,
      roles: ["admin", "lawyer", "clerk"]  // Specify who can access
    },
  },
  {
    path: "/audittrail",
    name: "AuditTrail",
    component: AuditTrail,
    meta: { 
      requiresAuth: true,
      roles: ["admin"]  // Usually only admin can view audit trail
    },
  },
  {
    path: "/dashboard",
    name: "Dashboard",
    component: Dashboard,
    meta: { 
      requiresAuth: true,
      roles: ["admin", "lawyer", "clerk"]  // All roles can access dashboard
    },
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

// ── Helpers ───────────────────────────────────────────────────────

function isAuthenticated() {
  return !!sessionStorage.getItem("token");
}

function getUserRole() {
  try {
    const user = JSON.parse(sessionStorage.getItem("user"));
    // Handles both { role: { name: "admin" } } and { role: "Admin" }
    const role = user?.role?.name ?? (typeof user?.role === "string" ? user.role.toLowerCase() : null);
    return role?.toLowerCase(); // Normalize to lowercase
  } catch {
    return null;
  }
}

// ── Navigation Guard ──────────────────────────────────────────────
router.beforeEach((to, from, next) => {
  const authenticated = isAuthenticated();
  const userRole = getUserRole();

  // Debug logging (remove in production)
  console.log('Navigation to:', to.path);
  console.log('Authenticated:', authenticated);
  console.log('User role:', userRole);
  console.log('Route meta:', to.meta);

  // Not logged in → redirect to login
  if (to.meta.requiresAuth && !authenticated) {
    return next("/");
  }

  // Already logged in → don't show login page
  if (to.meta.guest && authenticated) {
    return next("/dashboard");
  }

  // Route requires specific roles
  if (to.meta.roles) {
    // Check if user has any of the required roles
    const hasRequiredRole = to.meta.roles.some(role => 
      role.toLowerCase() === userRole?.toLowerCase()
    );
    
    if (!hasRequiredRole) {
      console.warn(`Access denied: User role "${userRole}" not in allowed roles:`, to.meta.roles);
      return next("/dashboard");
    }
  }
  // Backward compatibility for single role
  else if (to.meta.role) {
    if (to.meta.role.toLowerCase() !== userRole?.toLowerCase()) {
      console.warn(`Access denied: User role "${userRole}" does not match required role:`, to.meta.role);
      return next("/dashboard");
    }
  }

  next();
});

export default router;