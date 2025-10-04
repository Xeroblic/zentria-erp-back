// En tu slice empresaSlice.ts, cambia la línea del patchEmpresaPrincipal:

export const patchEmpresaPrincipal = createAsyncThunk<
  IEmpresa,
  Partial<IEmpresa>,
  { rejectValue: string }
>(
  'empresa/patchEmpresaPrincipal',
  async (empresaData, { rejectWithValue }) => {
    try {
      const empresa = await ApiService.fetchNormalized<IEmpresa>({
        url: '/companies/1',
        method: 'put', // ← Cambiar de 'patch' a 'put'
        data: empresaData,
      });
      return empresa;
    } catch (error: any) {
      return rejectWithValue(error.response?.data?.message || 'Error actualizando la empresa principal');
    }
  }
);
