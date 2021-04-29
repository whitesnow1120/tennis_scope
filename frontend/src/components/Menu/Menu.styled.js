import styled from 'styled-components';

export const StyledMenu = styled.nav`
  display: block;
  font-family: 'Nunito', sans-serif;
  justify-content: center;
  background: ${({ theme }) => theme.primaryLight};
  transform: ${({ open }) => (open ? 'translateX(0)' : 'translateX(-100%)')};
  height: auto;
  text-align: left;
  margin: 0;
  padding: 0;
  position: fixed;
  color: #474D5C;
  top: 0;
  left: 0;
  z-index: 9000;
  transition: transform 0.3s ease-in-out;
  @media (max-width: ${({ theme }) => theme.mobile}) {
    width: 100%;
  }
  a {
    font-size: 2rem;
    text-transform: uppercase;
    padding: 2rem 0;
    font-weight: bold;
    letter-spacing: 0.5rem;
    text-decoration: none;
    transition: color 0.3s linear;
    @media (max-width: ${({ theme }) => theme.mobile}) {
      font-size: 1.5rem;
      text-align: center;
    }
    &:hover {
      color: ${({ theme }) => theme.primaryHover};
    }
  }
`;
