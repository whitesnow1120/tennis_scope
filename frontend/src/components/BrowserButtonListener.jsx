import { useEffect } from 'react';

const BrowserButtonListener = (props) => {
  const { setBrowserButtonPressed } = props;

  useEffect(() => {
    window.onpopstate = () => {
      setBrowserButtonPressed(true);
    };
  });
  return null;
};

export default BrowserButtonListener;
