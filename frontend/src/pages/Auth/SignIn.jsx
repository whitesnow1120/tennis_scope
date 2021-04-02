import React, { useState } from 'react';
import { Button, Form, FormGroup, Label, Input } from 'reactstrap';
import { Redirect } from 'react-router-dom';
import { useDispatch } from 'react-redux';

import { GET_USER_STATUS } from '../../store/actions/types';
import { login } from '../../apis';

const SignIn = () => {
  const dispatch = useDispatch();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [msg, setMsg] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [redirect, setRedirect] = useState(false);
  const [errMsgUsername, setErrMsgUsername] = useState('');
  const [errMsgPwd, setErrMsgPwd] = useState('');
  const [errMsg, setErrMsg] = useState('');
  const isLoggedIn = localStorage.getItem('isLoggedIn');

  const handleSignIn = async () => {
    setIsLoading(true);
    const params = {
      username,
      password,
    };
    const response = await login(params);
    if (response.data.success) {
      localStorage.setItem('isLoggedIn', true);
      localStorage.setItem('userData', JSON.stringify(response.data.data));
      dispatch({ type: GET_USER_STATUS, payload: true });
      setMsg(response.data.message);
      setRedirect(true);
    } else {
      if (response.data.success === undefined) {
        setErrMsgUsername(response.data.validation_error.username);
        setErrMsgPwd(response.data.validation_error.password);
        setTimeout(() => {
          setErrMsgUsername('');
          setErrMsgPwd('');
        }, 4000);
      } else if (response.data.success === false) {
        setErrMsg(response.data.message);
        setTimeout(() => {
          setErrMsg('');
        }, 4000);
      }
      dispatch({ type: GET_USER_STATUS, payload: false });
    }
    setIsLoading(false);
  };

  if (redirect || isLoggedIn) {
    return <Redirect to="/inplay" />;
  } else {
    return (
      <div className="login">
        <Form className="login-containers">
          <FormGroup>
            <Label for="username">Username</Label>
            <Input
              type="text"
              name="username"
              placeholder="Enter username"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
            />
            <span className="text-danger">{msg}</span>
            <span className="text-danger">{errMsgUsername}</span>
          </FormGroup>
          <FormGroup>
            <Label for="password">Password</Label>
            <Input
              type="password"
              name="password"
              placeholder="Enter password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
            <span className="text-danger">{errMsgPwd}</span>
          </FormGroup>
          <p className="text-danger">{errMsg}</p>
          <Button
            className="text-center mb-4"
            color="success"
            onClick={handleSignIn}
          >
            Sign In
            {isLoading ? (
              <span
                className="spinner-border spinner-border-sm ml-5"
                role="status"
                aria-hidden="true"
              ></span>
            ) : (
              <></>
            )}
          </Button>
        </Form>
      </div>
    );
  }
};

export default SignIn;
