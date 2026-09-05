import React, { createContext, useContext, useState, useEffect } from 'react';
import API from '../services/api';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('box_token');
    const storedUser = localStorage.getItem('box_user');

    if (token && storedUser) {
      try {
        setUser(JSON.parse(storedUser));
      } catch (e) {
        localStorage.removeItem('box_token');
        localStorage.removeItem('box_user');
      }
    }
    setLoading(false);
  }, []);

  const login = async (username, password, requiredRole = null) => {
    const res = await API.post('/auth/login', { username, password });
    const { token, user: userData } = res.data;

    if (requiredRole && userData.role !== requiredRole) {
      throw new Error(`Access Denied: Account does not have ${requiredRole} privileges.`);
    }

    localStorage.setItem('box_token', token);
    localStorage.setItem('box_user', JSON.stringify(userData));
    setUser(userData);
    return userData;
  };

  const logout = () => {
    localStorage.removeItem('box_token');
    localStorage.removeItem('box_user');
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, login, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
