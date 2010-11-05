#include <stdio.h>
#include <assert.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/uio.h>
#include <fcntl.h>
#include <unistd.h>
#include <dirent.h>

#define __term__(name) \
    fprintf(stderr, "\nAccess to dangerous function %s() denied.\n", #name); \
    assert(0);
    
#ifdef COMPILER_GPP
extern "C" {
#endif
	int execl(const char *path, const char *arg, ...) {
	__term__(execl);
	}
	
	int execlp(const char *file, const char *arg, ...) {
	__term__(execlp);
	}
	
	int  execle(const  char  *path,  const  char  *arg  , ...) {
	__term__(execle);
	}
	
	int execv(const char *path, char *const argv[]) {
	__term__(execv);
	}
	
	int execvp(const char *file, char *const argv[]) {
	__term__(execvp);
	}
	
	pid_t fork(void) {
	__term__(fork);
	}
	
	int  execve(const  char  *filename,  char  *const  argv [], char *const
		envp[]) {
	__term__(execve);
	}
	
	unsigned int sleep(unsigned int seconds) {
	__term__(sleep);
	}
	
	int usleep(unsigned int usec) {
	__term__(usleep);
	}
	
	pid_t vfork(void) {
	__term__(vfork);
	}
	
	pid_t wait(int *status) {
	__term__(wait);
	}
	
	pid_t waitpid(pid_t pid, int *status, int options) {
	__term__(waitpid);
	}
	
	/*int open(const char *pathname, int flags, ...) {
	__term__(open);
	}*/
	
	int creat(const char *pathname, mode_t mode) {
	__term__(creat);
	}
	
	ssize_t write(int fd, const void *buf, size_t count) {
	__term__(write);
	}
	
	ssize_t writev(int fd, const struct iovec *vector, int count) {
	__term__(writev);
	}
#ifdef COMPILER_GPP
    int clone(int (*fn)(void *), void *child_stack, int flags, void *arg, ...) __THROW {
#else
	int clone(int (*fn)(void *), void *child_stack, int flags, void *arg, ...) {
#endif
	__term__(clone);
	}
	
	DIR *opendir(const char *name) {
	__term__(opendir);
	}
	
	int unlink(const char *pathname) {
	__term__(unlink);
	}
	
	int rename(const char *oldpath, const char *newpath) {
	__term__(rename);
	}
	
	int remove(const char *pathname) {
	__term__(remove);
	}
	
	FILE* fopen(const char* path, const char* mode) {
	__term__(fopen);
	}
	
	FILE *fdopen(int fildes, const char *mode) {
	__term__(fdopen);
	}
	
	FILE *freopen(const char *path, const char *mode, FILE *stream) {
	__term__(freopen);
	}
	
	int system(const char *string) {
	__term__(system);
	}

#ifdef COMPILER_GPP
}
#endif

