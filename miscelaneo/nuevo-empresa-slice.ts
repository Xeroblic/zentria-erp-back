import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import ApiService from '@/services/ApiService';
import { IEmpresa, IUsuarioEmpresa } from '@/interface/empresas.interface';

export interface EmpresaState {
  loading: boolean;
  error?: string;
  // Empresa del usuario actual (dinámico)
  miEmpresa?: IEmpresa;
  miEmpresaSubsidiarias: IEmpresa['subsidiaries'];
  miEmpresaUsuarios?: IEmpresa;
  // Para casos especiales donde necesites todas las empresas (solo super-admin)
  todasLasEmpresas: IEmpresa[];
  // Estados de invitación
  inviteLoading: boolean;
  inviteError?: string;
  inviteResponse?: { usuario: IUsuarioEmpresa; password_temporal: string };
}

const initialState: EmpresaState = {
  loading: false,
  error: undefined,
  miEmpresa: undefined,
  miEmpresaSubsidiarias: [],
  miEmpresaUsuarios: undefined,
  todasLasEmpresas: [],
  inviteLoading: false,
  inviteError: undefined,
  inviteResponse: undefined,
};

// ========== ENDPOINTS DINÁMICOS (SIN HARDCODING) ==========

// Obtener MI empresa (la empresa del usuario logueado)
export const fetchMiEmpresa = createAsyncThunk<IEmpresa, void, { rejectValue: string }>(
  'empresa/fetchMiEmpresa',
  async (_, { rejectWithValue }) => {
    try {
      const empresa = await ApiService.fetchNormalized<IEmpresa>({
        url: '/my-company',
        method: 'get'
      });
      return empresa;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Error al cargar tu empresa');
    }
  }
);

// Actualizar MI empresa
export const updateMiEmpresa = createAsyncThunk<
  IEmpresa,
  Partial<IEmpresa>,
  { rejectValue: string }
>(
  'empresa/updateMiEmpresa',
  async (empresaData, { rejectWithValue }) => {
    try {
      const empresa = await ApiService.fetchNormalized<IEmpresa>({
        url: '/my-company',
        method: 'put',
        data: empresaData,
      });
      return empresa;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Error actualizando tu empresa');
    }
  }
);

// Obtener subsidiarias de MI empresa
export const fetchMiEmpresaSubsidiarias = createAsyncThunk<
  { subempresas: IEmpresa['subsidiaries']; empresa: { id: number; nombre: string } },
  void,
  { rejectValue: string }
>(
  'empresa/fetchMiEmpresaSubsidiarias',
  async (_, { rejectWithValue }) => {
    try {
      const response = await ApiService.fetchData<{
        subempresas: IEmpresa['subsidiaries'];
        empresa: { id: number; nombre: string };
      }>({
        url: '/my-company/subsidiaries',
        method: 'get',
      });
      return response.data;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Error al cargar subsidiarias');
    }
  }
);

// Obtener usuarios de MI empresa
export const fetchMiEmpresaUsuarios = createAsyncThunk<
  { usuarios: any[]; empresa: { id: number; nombre: string } },
  void,
  { rejectValue: string }
>(
  'empresa/fetchMiEmpresaUsuarios',
  async (_, { rejectWithValue }) => {
    try {
      const response = await ApiService.fetchData<{
        usuarios: any[];
        empresa: { id: number; nombre: string };
      }>({
        url: '/my-company/users',
        method: 'get',
      });
      return response.data;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Error al cargar usuarios de tu empresa');
    }
  }
);

// ========== ENDPOINTS LEGACY (PARA COMPATIBILIDAD) ==========

// Obtener todas las empresas (solo para super-admin global)
export const fetchTodasLasEmpresas = createAsyncThunk<IEmpresa[], void, { rejectValue: string }>(
  'empresa/fetchTodasLasEmpresas',
  async (_, { rejectWithValue }) => {
    try {
      const empresas = await ApiService.fetchNormalized<IEmpresa[]>({
        url: '/companies',
        method: 'get'
      });
      return empresas;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Error al cargar todas las empresas');
    }
  }
);

// Invitar usuario a MI empresa (dinámico)
export const inviteUsuarioAMiEmpresa = createAsyncThunk<
  { usuario: IUsuarioEmpresa; password_temporal: string },
  { nombre: string; email: string },
  { rejectValue: string }
>(
  'empresa/inviteUsuarioAMiEmpresa',
  async ({ nombre, email }, { rejectWithValue, getState }) => {
    try {
      // Obtener el ID de mi empresa desde el estado
      const state = getState() as { empresa: EmpresaState };
      const miEmpresaId = state.empresa.miEmpresa?.id;
      
      if (!miEmpresaId) {
        return rejectWithValue('No se pudo determinar tu empresa. Recarga la página.');
      }

      const response = await ApiService.fetchData<{ usuario: IUsuarioEmpresa; password_temporal: string }>({
        url: `/empresas/${miEmpresaId}/invitar`,
        method: 'post',
        data: { nombre, email },
      });
      return response.data;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Error invitando usuario');
    }
  }
);

// Slice
const empresaSlice = createSlice({
  name: 'empresa',
  initialState,
  reducers: {
    // Limpiar errores
    clearErrors: (state) => {
      state.error = undefined;
      state.inviteError = undefined;
    },
    // Limpiar respuesta de invitación
    clearInviteResponse: (state) => {
      state.inviteResponse = undefined;
    },
  },
  extraReducers: (builder) => {
    builder
      // MI EMPRESA (dinámico)
      .addCase(fetchMiEmpresa.pending, (state) => {
        state.loading = true;
        state.error = undefined;
      })
      .addCase(fetchMiEmpresa.fulfilled, (state, { payload }) => {
        state.loading = false;
        state.miEmpresa = payload;
      })
      .addCase(fetchMiEmpresa.rejected, (state, { payload }) => {
        state.loading = false;
        state.error = payload as string;
      })

      // ACTUALIZAR MI EMPRESA
      .addCase(updateMiEmpresa.pending, (state) => {
        state.loading = true;
        state.error = undefined;
      })
      .addCase(updateMiEmpresa.fulfilled, (state, { payload }) => {
        state.loading = false;
        state.miEmpresa = payload;
      })
      .addCase(updateMiEmpresa.rejected, (state, { payload }) => {
        state.loading = false;
        state.error = payload;
      })

      // SUBSIDIARIAS DE MI EMPRESA
      .addCase(fetchMiEmpresaSubsidiarias.pending, (state) => {
        state.loading = true;
        state.error = undefined;
      })
      .addCase(fetchMiEmpresaSubsidiarias.fulfilled, (state, { payload }) => {
        state.loading = false;
        state.miEmpresaSubsidiarias = payload.subempresas;
        // Actualizar info de empresa si está disponible
        if (!state.miEmpresa && payload.empresa) {
          state.miEmpresa = { ...state.miEmpresa, id: payload.empresa.id, company_name: payload.empresa.nombre } as IEmpresa;
        }
      })
      .addCase(fetchMiEmpresaSubsidiarias.rejected, (state, { payload }) => {
        state.loading = false;
        state.error = payload;
      })

      // USUARIOS DE MI EMPRESA
      .addCase(fetchMiEmpresaUsuarios.pending, (state) => {
        state.loading = true;
        state.error = undefined;
      })
      .addCase(fetchMiEmpresaUsuarios.fulfilled, (state, { payload }) => {
        state.loading = false;
        // Aquí puedes manejar los usuarios como necesites
        state.miEmpresaUsuarios = { usuarios: payload.usuarios } as any;
      })
      .addCase(fetchMiEmpresaUsuarios.rejected, (state, { payload }) => {
        state.loading = false;
        state.error = payload;
      })

      // TODAS LAS EMPRESAS (solo super-admin)
      .addCase(fetchTodasLasEmpresas.pending, (state) => {
        state.loading = true;
        state.error = undefined;
      })
      .addCase(fetchTodasLasEmpresas.fulfilled, (state, { payload }) => {
        state.loading = false;
        state.todasLasEmpresas = payload;
      })
      .addCase(fetchTodasLasEmpresas.rejected, (state, { payload }) => {
        state.loading = false;
        state.error = payload as string;
      })

      // INVITAR USUARIO
      .addCase(inviteUsuarioAMiEmpresa.pending, (state) => {
        state.inviteLoading = true;
        state.inviteError = undefined;
      })
      .addCase(inviteUsuarioAMiEmpresa.fulfilled, (state, { payload }) => {
        state.inviteLoading = false;
        state.inviteResponse = payload;
      })
      .addCase(inviteUsuarioAMiEmpresa.rejected, (state, { payload }) => {
        state.inviteLoading = false;
        state.inviteError = payload;
      });
  },
});

export const { clearErrors, clearInviteResponse } = empresaSlice.actions;
export default empresaSlice.reducer;
