function loginpage(){
    const loginModal= document.getElementById('loginModal');
    loginModal.classList.add('show');
    document.body.classList.add('modal-open');
}

function closeLoginModal(){
    const loginModal= document.getElementById('loginModal');
    loginModal.classList.remove('show');
    document.body.classList.remove('modal-open');
}   

function signuppage(){
    const signupModal= document.getElementById('signupModal');
    signupModal.classList.add('show');
    document.body.classList.add('modal-open');
}

function closeSignupModal(){
    const signupModal= document.getElementById('signupModal');
    signupModal.classList.remove('show');
    document.body.classList.remove('modal-open');
}

window.addEventListener('click', function(event) {
    const loginModal = document.getElementById('loginModal');
    const signupModal = document.getElementById('signupModal'); 
    if (event.target === loginModal) {
        closeLoginModal();
    }   
    if (event.target === signupModal) {
        closeSignupModal();
    }   
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLoginModal();
        closeSignupModal();
    }
});